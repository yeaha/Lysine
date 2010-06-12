<?php
class Ly_Application {
    static public $instance;
    protected $urls;
    protected $include_path = array();
    protected $class_map = array();
    protected $config = array();

    // 可以把需要在不同地方共享的数据放这里
    // 避免使用全局变量
    protected $registry = array();

    public function __construct() {
        if (!defined('APP_PATH')) die('please define APP_PATH constant');

        $this->config = require LY_PATH .'/base/config.php';
        spl_autoload_register(array($this, 'autoload'));
    }

    static public function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function set($key, $val) {
        if ($val === false) {
            unset($this->registry[$key]);
        } else {
            $this->registry[$key] = $val;
        }
        return $this;
    }

    public function get($key, $default = false) {
        return array_key_exists($key, $this->registry) ? $this->registry[$key] : $default;
    }

    public function setConfig(array $config) {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }

    public function getConfig() {
        $args = func_get_args();
        return array_spider($this->config, $args);
    }

    public function includePath($path) {
        if (is_array($path)) {
            foreach ($path as $p) $this->includePath($p);
        } else {
            $this->include_path[] = realpath($path);
        }
        return $this;
    }

    public function includeClassMap(array $map) {
        $this->class_map = $map;
        return $this;
    }

    public function autoload($class) {
        if (class_exists($class, false) || interface_exists($class, false)) return true;

        // 从class_map找到类所在文件直接载入
        if (array_key_exists($class, $this->class_map)) {
            $file = APP_PATH .'/'. $this->class_map[$class];
            if (is_readable($file)) require $file;
            if (class_exists($class, false) || interface_exists($class, false)) return true;
        }

        // 从所有的include_path里尝试查找
        $find = str_replace('_', '/', strtolower($class)) .'.php';
        foreach ($this->include_path as $path) {
            $file = $path .'/'. $find;
            if (!is_readable($file)) continue;

            require $file;
            if (class_exists($class, false) || interface_exists($class, false)) return true;
        }
        return false;
    }

    protected function _matchRequest($url) {
        $urls = $this->urls;

        while (list($re, $class) = each($urls)) {
            if (preg_match($re, $url, $match)) {
                array_shift($match);

                return array($class, $match);
            }
        }

        return false;
    }

    protected function _dispatch($url, array $options = null) {
        $search = $this->_matchRequest($url);
        if ($search === false)
            throw new Ly_Request_Exception('Page Not Found', 404);

        list($class, $args) = $search;
        if ($options) $args = array_merge($args, $options);

        $req = req();
        $req_method = $req->requestMethod();

        $fn = $req_method;
        if ($req->isAJAX()) {
            if (method_exists($class, 'ajax')) $fn = 'ajax';
            if (method_exists($class, 'ajax_'.$req_method)) $fn = 'ajax_'.$req_method;
        }

        $handle = new $class();

        if (method_exists('preRun', $handle)) {
            // 如果preRun返回了内容，就直接完成动作
            // 可以在这里进行某些阻断操作
            // 正常的内容不应该通过这里输出
            $resp = call_user_func_array(array($handle, 'preRun'), $args);
            if ($resp) return $resp;
        }

        // 不使用method_exists()检查，用is_callable()
        // 保留__call()重载方法的方式
        if (!is_callable(array($handle, $fn)))
            throw new Ly_Request_Exception('Not Acceptable', 406);
        $resp = call_user_func_array(array($handle, $fn), $args);

        // 把response结果作为参数传递给postRun
        if (method_exists('postRun', $handle)) call_user_func(array($handle, 'postRun'), &$resp);

        return $resp;
    }

    public function redirect($url, $code = 303) {
        return new Ly_Response_Redirect($url, $code);
    }

    public function forward($url, array $options = null) {
        return $this->_dispatch($url, $options);
    }

    public function run(array $urls) {
        $this->urls = $urls;

        $req = req();
        if (!in_array($req->requestMethod(), array('get', 'post', 'put', 'delete')))
            throw new Ly_Request_Exception('Method Not Allowed', 405);

        $resp = $this->_dispatch($req->requestUri());

        return $resp;
    }
}
