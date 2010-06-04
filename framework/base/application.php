<?php
class Ly_Application {
    static public $instance;
    protected $urls;
    protected $base_uri;
    protected $include_path = array();
    protected $class_map = array();
    protected $config = array();

    // 可以把需要在不同地方共享的数据放这里
    // 避免使用全局变量
    protected $registry = array();

    public function __construct() {
        if (!defined('APP_PATH')) die('please define APP_PATH constant');
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
        $this->config = $config;
        return $this;
    }

    public function getConfig() {
        return array_spider($this->config, func_get_args());
    }

    public function setBaseUri($base_uri) {
        $this->base_uri = $base_uri;
        return $this;
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
                unset($match[0]);

                return array($class, $match);
            }
        }

        return false;
    }

    public function run(array $urls, $include_path = null) {
        $this->urls = $urls;
        if ($include_path) $this->includePath($include_path);

        $req = req();
        $req_method = $req->requestMethod();
        if (!in_array($req_method, array('get', 'post', 'put', 'delete'))) die();
        $ajax = $req->isAJAX();

        $request_uri = $req->requestUri();
        if ($this->base_uri) {
            $request_uri = str_replace($this->base_uri, '', $request_uri);
            if (substr($request_uri, 0, 1) != '/') $request_uri = '/'. $request_uri;
        }

        $search = $this->_matchRequest($request_uri);
        if ($search === false) die();

        list($class, $args) = $search;

        $fn = $req_method;
        if ($ajax) {
            if (method_exists($class, 'ajax')) $fn = 'ajax';
            if (method_exists($class, 'ajax_'.$req_method)) $fn = 'ajax_'.$req_method;
        }

        $handle = new $class();

        if (method_exists('preRun', $handle)) call_user_func_array(array($handle, 'preRun'), $args);
        $rep = call_user_func_array(array($handle, $fn), $args);
        if (method_exists('postRun', $handle)) call_user_func_array(array($handle, 'postRun'), $args);

        return $rep;
    }
}
