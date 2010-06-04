<?php
class Ly_Request {
    static public $instance;
    protected $_requestUri;

    static public function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function __get($key) {
        return $this->request($key);
    }

    protected function _getFrom($source, $key = null, $default = false) {
        if (is_null($key)) return $source;
        return isset($source[$key]) ? $source[$key] : $default;
    }

    public function get($key = null, $default = false) {
        return $this->_getFrom($_GET, $key, $default);
    }

    public function post($key = null, $default = false) {
        return $this->_getFrom($_POST, $key, $default);
    }

    public function request($key = null, $default = false) {
        return $this->_getFrom($_REQUEST, $key, $default);
    }

    public function env($key = null, $default = false) {
        return $this->_getFrom($_ENV, strtoupper($key), $default);
    }

    public function server($key = null, $default = false) {
        return $this->_getFrom($_SERVER, strtoupper($key), $default);
    }

    public function file() {
    }

    public function cookie() {
        $result = $_COOKIE;
        foreach (func_get_args() as $arg) {
            if (!is_array($result)) return false;
            if (!array_key_exists($arg, $result)) return false;

            $result = $result[$arg];
        }

        return $result;
    }

    public function session() {
        if (!isset($_SESSION)) return false;

        $result = $_SESSION;
        foreach (func_get_args() as $arg) {
            if (!is_array($result)) return false;
            if (!array_key_exists($arg, $result)) return false;

            $result = $result[$arg];
        }

        return $result;
    }

    public function header($key) {
        $skey = 'http_'. str_replace('-', '_', $key);
        $sval = $this->server($skey);
        if ($sval) return $sval;

        return false;
    }

    public function requestMethod() {
        $method = $this->header('x-http-method-override');
        return strtolower( $method ? $method : $this->server('request_method') );
    }

    public function requestUri() {
        if ($this->_requestUri !== null) return $this->_requestUri;

        $uri = $this->server('http_x_rewrite_url');
        if ($uri) return $this->_requestUri = $uri;

        $uri = $this->server('request_uri');
        if ($uri) return $this->_requestUri = $uri;

        $uri = $this->server('orig_path_info');
        if ($uri) {
            $query = $this->server('query_string');
            if (!empty($query)) $uri .= '?'. $query;
            return $this->_requestUri = $uri;
        }

        throw new Exception('Unable to get request URI');
    }

    public function requestBaseUri() {
        $uri = $this->requestUri();
        $pos = strpos($uri, '?');
        if ($pos !== false) $uri = substr($uri, 0, $pos);
        return $uri;
    }

    public function isGET() {
        return $this->requestMethod() === 'get';
    }

    public function isPOST() {
        return $this->requestMethod() === 'post';
    }

    public function isPUT() {
        return $this->requestMethod() === 'put';
    }

    public function isDELETE() {
        return $this->requestMethod() === 'delete';
    }

    public function isAJAX() {
        return strtolower($this->header('X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    protected function _getAccept($key) {
        $result = array();
        if (!$accept = $this->server($key)) return $result;

        foreach (explode(',', $accept) as $accept) {
            $pos = strpos($accept, ';');
            if ($pos !== false) $accept = substr($accept, 0, $pos);
            $result[] = strtolower(trim($accept));
        }

        return $result;
    }

    public function acceptTypes() {
        return $this->_getAccept('http_accept');
    }

    public function acceptLang() {
        return $this->_getAccept('http_accept_language');
    }

    public function acceptCharset() {
        return $this->_getAccept('http_accept_charset');
    }

    public function acceptEncoding() {
        return $this->_getAccept('http_accept_encoding');
    }

    public function referer() {
        return $this->server('http_referer');
    }

    public function ip() {
        return $this->server('remote_addr', '127.0.0.1');
    }
}
