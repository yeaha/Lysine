<?php
class Ly_Application {
    static public $instance;
    protected $include_path = array();
    protected $config = array();

    // 可以把需要在不同地方共享的数据放这里
    // 避免使用全局变量
    protected $registry = array();

    public function __construct() {
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
        $args = func_get_args();

        $result = $this->config;
        foreach ($args as $arg) {
            if (!is_array($result)) return false;
            if (!array_key_exists($arg, $result)) return false;

            $result = $result[$arg];
        }

        return $result;
    }

    public function includePath($path) {
        if (is_array($path)) {
            foreach ($path as $p) $this->includePath($p);
        } else {
            $this->include_path[] = realpath($path);
        }
    }

    public function autoload($class_name) {
        if (class_exists($class_name, false) || interface_exists($class_name, false)) return true;
        $find = str_replace('_', '/', strtolower($class_name)) .'.php';

        foreach ($this->include_path as $path) {
            $file = $path .'/'. $find;
            if (!is_readable($file)) continue;

            require $file;
            if (class_exists($class_name, false) || interface_exists($class_name, false)) return false;
            return true;
        }
        return false;
    }

    public function run(array $urls, $include_path = null) {
        if ($include_path) $this->includePath($include_path);

        $req = req();
        $base_uri = $req->requestBaseUri();

        $method = $req->requestMethod();
        $ajax = $req->isAJAX();

        while (list($re, $class) = each($urls)) {
            if (!preg_match($re, $base_uri, $match)) continue;

            $fn = $method;
            if ($ajax) {
                if (method_exists($class, 'ajax_'.$method)) $fn = 'ajax_'.$method;
                if (method_exists($class, 'ajax')) $fn = 'ajax';
            }

            array_shift($match);
            $handle = new $class();

            if (method_exists('preRun', $handle)) call_user_func_array(array($handle, 'preRun'), $match);
            $rep = call_user_func_array(array($handle, $fn), $match);
            if (method_exists('postRun', $handle)) call_user_func_array(array($handle, 'postRun'), $match);
            break;
        }
        return $rep;
    }
}
