<?php
class Ly_Application {
    static public $instance;
    protected $include_path = array();

    public function __construct() {
        spl_autoload_register(array($this, 'autoload'));
    }

    static public function instance(array $urls = null, array $config = null) {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function setBasePath($base_path) {
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

    public static function run(array $urls, $include_path = null) {
        $app = self::instance();
        if ($include_path) $app->includePath($include_path);

        $req = req();
        $base_uri = $req->requestBaseUri();

        $method = $req->requestMethod();
        $ajax = $req->isAJAX();

        foreach ($urls as $re => $class) {
            if (!preg_match($re, $base_uri, $match)) continue;

            $fn = $method;
            if ($ajax) {
                if (method_exists($class, 'ajax_'.$method)) $fn = 'ajax_'.$method;
                if (method_exists($class, 'ajax')) $fn = 'ajax';
            }

            array_shift($match);
            $handle = new $class();

            if (method_exists('preRun', $handle)) $handle->preRun();
            $rep = call_user_func_array(array(new $class(), $fn), $match);
            if (method_exists('postRun', $handle)) $handle->postRun();
            break;
        }
        return $rep;
    }
}
