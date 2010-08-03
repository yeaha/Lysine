<?php
namespace Lysine;

use Lysine\Utils\Injection;

class Application extends Injection {
    static public $instance;

    protected $router;

    protected $class_map = array();
    protected $include_path = array();
    protected $registry = array();

    static public function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        spl_autoload_register(array($this, 'autoload'));
    }

    public function setRouter(Router_Abstract $router = null) {
        $this->router = $router;
        return $this;
    }

    public function getRouter() {
        if (!$this->router) $this->router = new Router();
        return $this->router;
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

    protected function dispatch($url, array $params = array()) {
        return $this->getRouter()->dispatch($url, $params);
    }

    public function redirect($url, $code = 303) {
        return new Response_Redirect($url, $code);
    }

    public function forward($url, array $options = null) {
        return $this->dispatch($url, $options);
    }

    public function run() {
        $req = req();
        if (!in_array($req->method(), array('get', 'post', 'put', 'delete')))
            throw Request_Exception('Method Not Allowed', 405);

        $url = parse_url($req->requestUri());
        return $this->dispatch($url['path']);
    }
}
