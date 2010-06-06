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

        if (method_exists('preRun', $handle)) call_user_func_array(array($handle, 'preRun'), $args);

        // 不使用method_exists()检查，用is_callable()
        // 保留__call()重载方法的方式
        if (!is_callable(array($handle, $fn)))
            throw new Ly_Request_Exception('Not Acceptable', 406);
        $resp = call_user_func_array(array($handle, $fn), $args);

        if (method_exists('postRun', $handle)) call_user_func_array(array($handle, 'postRun'), $args);

        return $resp;
    }

    public function redirect($url, $code = 303) {
        header(self::httpStatus($code));
        header('Location: '. $url);
        return null;
    }

    public function forward($url, array $options = null) {
        return $this->_dispatch($url, $options);
    }

    public function run(array $urls) {
        $this->urls = $urls;

        $req = req();
        if (!in_array($req->requestMethod(), array('get', 'post', 'put', 'delete')))
            throw new Ly_Request_Exception('Method Not Allowed', 405);

        $request_uri = $req->requestUri();
        if ($this->base_uri) {
            $request_uri = str_replace($this->base_uri, '', $request_uri);
            if (substr($request_uri, 0, 1) != '/') $request_uri = '/'. $request_uri;
        }

        $resp = $this->_dispatch($request_uri);

        return $resp;
    }

    static public function httpStatus($code) {
        $http = array (
            100 => 'HTTP/1.1 100 Continue',
            101 => 'HTTP/1.1 101 Switching Protocols',
            200 => 'HTTP/1.1 200 OK',
            201 => 'HTTP/1.1 201 Created',
            202 => 'HTTP/1.1 202 Accepted',
            203 => 'HTTP/1.1 203 Non-Authoritative Information',
            204 => 'HTTP/1.1 204 No Content',
            205 => 'HTTP/1.1 205 Reset Content',
            206 => 'HTTP/1.1 206 Partial Content',
            300 => 'HTTP/1.1 300 Multiple Choices',
            301 => 'HTTP/1.1 301 Moved Permanently',
            302 => 'HTTP/1.1 302 Found',
            303 => 'HTTP/1.1 303 See Other',
            304 => 'HTTP/1.1 304 Not Modified',
            305 => 'HTTP/1.1 305 Use Proxy',
            307 => 'HTTP/1.1 307 Temporary Redirect',
            400 => 'HTTP/1.1 400 Bad Request',
            401 => 'HTTP/1.1 401 Unauthorized',
            402 => 'HTTP/1.1 402 Payment Required',
            403 => 'HTTP/1.1 403 Forbidden',
            404 => 'HTTP/1.1 404 Not Found',
            405 => 'HTTP/1.1 405 Method Not Allowed',
            406 => 'HTTP/1.1 406 Not Acceptable',
            407 => 'HTTP/1.1 407 Proxy Authentication Required',
            408 => 'HTTP/1.1 408 Request Time-out',
            409 => 'HTTP/1.1 409 Conflict',
            410 => 'HTTP/1.1 410 Gone',
            411 => 'HTTP/1.1 411 Length Required',
            412 => 'HTTP/1.1 412 Precondition Failed',
            413 => 'HTTP/1.1 413 Request Entity Too Large',
            414 => 'HTTP/1.1 414 Request-URI Too Large',
            415 => 'HTTP/1.1 415 Unsupported Media Type',
            416 => 'HTTP/1.1 416 Requested range not satisfiable',
            417 => 'HTTP/1.1 417 Expectation Failed',
            500 => 'HTTP/1.1 500 Internal Server Error',
            501 => 'HTTP/1.1 501 Not Implemented',
            502 => 'HTTP/1.1 502 Bad Gateway',
            503 => 'HTTP/1.1 503 Service Unavailable',
            504 => 'HTTP/1.1 504 Gateway Time-out',
        );

        return $http[$code];
    }
}
