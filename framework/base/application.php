<?php
namespace Lysine;

class Application {
    static $instance;

    protected $router;

    protected $config = array();
    protected $url_map = array();
    protected $class_map = array();
    protected $include_path = array();
    protected $registry = array();

    static public function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __construct(Application\IRouter $router = null) {
        if ($router) $this->router = $router;

        spl_autoload_register(array($this, 'autoload'));
    }

    public function includeClassMap(array $map) {
        $this->class_map = array_merge($this->class_map, $map);
        return $this;
    }

    public function includePath($path) {
        if (is_array($path)) {
            $this->include_path = array_merge($this->include_path, $path);
        } else {
            $this->include_path[] = $path;
        }

        return $this;
    }

    protected function autoload($class) {
        // 从class_map数组中查询
        $class_map = $this->class_map;
        if (array_key_exists($class, $class_map)) {
            $file = $class_map[$class];

            if (is_readable($file)) include $file;
            if (class_exists($class, false) OR interface_exists($class, false)) return true;
        }

        $pos = strpos($class, '_', strrpos($class, '\\'));
        $find = ($pos === false) ? $class : substr($class, 0, $pos);
        $find = str_replace('\\', '/', strtolower($find)) .'.php';
        foreach ($this->include_path as $path) {
            $file = $path .'/'. $find;
            if (!is_readable($file)) continue;

            include $file;
            return class_exists($class, false) || interface_exists($class, false);
        }

        return false;
    }

    public function set($key, $val) {
        $this->registry[$key] = $val;
        return $this;
    }

    public function get($key, $default = false) {
        return array_key_exists($key, $this->registry) ? $this->registry[$key] : $default;
    }

    public function setConfig($config) {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }

    public function getConfig() {
        $args = func_get_args();
        return array_spider($this->config, $args);
    }

    protected function matchRequest($url) {
        foreach ($this->url_map as $re => $class) {
            if (preg_match($re, $url, $match))
                return array($class, array_slice($match, 1));
        }

        return false;
    }

    protected function dispatch($url, array $options = null) {
        $match = $this->matchRequest($url);
        if ($match === false)
            throw new Request_Exception('Page Not Found', 404);

        list($class, $args) = $match;
        if ($options) $args = array_merge($args, $options);

        $req = req();
        $method = $req->method();

        $fn = $method;
        if ($req->isAJAX()) {
            if (method_exists($class, 'ajax_'. $method)) {
                $fn = 'ajax_'. $method;
            } elseif (method_exists($class, 'ajax')) {
                $fn = 'ajax';
            }
        }

        $handle = new $class();

        if (method_exists($handle, 'preRun')) {
            // 如果preRun返回了内容，就直接完成动作
            // 可以在这里进行某些阻断操作
            // 正常的内容不应该通过这里输出
            $resp = call_user_func_array(array($handle, 'preRun'), $args);
            if ($resp) return $resp;
        }

        // 不使用method_exists()检查，用is_callable()
        // 保留__call()重载方法的方式
        if (!is_callable(array($handle, $fn)))
            throw new Request_Exception('Not Acceptable', 406);
        $resp = call_user_func_array(array($handle, $fn), $args);

        // response数据以引用方式传递给postRun
        // 这里有机会对输出结果进行进一步处理
        if (method_exists($handle, 'postRun')) call_user_func(array($handle, 'postRun'), &$resp);

        return $resp;
    }

    public function redirect($url, $code = 303) {
        return new Response_Redirect($url, $code);
    }

    public function forward($url, array $options = null) {
        return $this->dispatch($url, $options);
    }

    public function run($url_map) {
        $this->url_map = $url_map;

        $req = req();
        if (!in_array($req->method(), array('get', 'post', 'put', 'delete')))
            throw Request_Exception('Method Not Allowed', 405);

        return $this->dispatch($req->requestUri());
    }
}

namespace Lysine\Application;
interface IRouter {
    public function dispatch($url, array $params = array());
}
