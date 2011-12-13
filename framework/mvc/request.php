<?php
namespace Lysine\MVC;

use Lysine\HttpError;

class Request {
    static private $instance;

    protected $method;

    protected $requestUri;

    protected $accept = array();

    public function __get($key) {
        return request($key);
    }

    public function get($key = null, $default = null) {
        return get($key, $default);
    }

    public function post($key = null, $default = null) {
        return post($key, $default);
    }

    public function put($key = null, $default = null) {
        return put($key, $default);
    }

    public function request($key = null, $default = null) {
        return request($key, $default);
    }

    public function env($key = null, $default = false) {
        return env($key, $default);
    }

    public function server($key = null, $default = false) {
        return server($key, $default);
    }

    public function file() {
    }

    public function cookie($key = null, $default = false) {
        return cookie($key, $default);
    }

    public function session($key = null, $default = false) {
        return session($key, $default);
    }

    public function header($key) {
        $skey = 'http_'. str_replace('-', '_', $key);
        if ($sval = server($skey)) return $sval;

        return false;
    }

    public function method() {
        if ($this->method) return $this->method;

        $method = strtoupper($this->header('x-http-method-override') ?: server('request_method'));
        if ($method != 'POST') return $this->method = $method;

        // 某些js库的ajax封装使用这种方式
        $method = post('_method', $method);
        unset($_POST['_method']);
        return $this->method = strtoupper($method);
    }

    public function requestUri() {
        if ($this->requestUri !== null) return $this->requestUri;

        if ($uri = server('http_x_rewrite_url') ?: server('request_uri')) return $this->requestUri = $uri;

        if ($uri = server('orig_path_info')) {
            $query = server('query_string');
            if (!empty($query)) $uri .= '?'. $query;
            return $this->requestUri = $uri;
        }

        throw new HttpError('Unable to get request URI', 500);
    }

    public function requestBaseUri() {
        $uri = $this->requestUri();
        $pos = strpos($uri, '?');
        if ($pos !== false) $uri = substr($uri, 0, $pos);
        return rtrim($uri, '/') ?: '/';
    }

    public function isGET() {
        return ($this->method() === 'GET') ?: $this->isHEAD();
    }

    public function isPOST() {
        return $this->method() === 'POST';
    }

    public function isPUT() {
        return $this->method() === 'PUT';
    }

    public function isDELETE() {
        return $this->method() === 'DELETE';
    }

    public function isHEAD() {
        return $this->method() === 'HEAD';
    }

    public function isAJAX() {
        return strtolower($this->header('X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    // 通过http header x-requested-with或user-agent判断
    public function isFlash() {
        return (strtolower($this->header('X_REQUESTED_WITH')) == 'flash')
            || (strpos(strtolower($this->header('USER_AGENT')), ' flash') !== false);
    }

    protected function _getAccept($key) {
        $result = array();
        if (!$accept = server($key)) return $result;

        foreach (explode(',', $accept) as $accept) {
            $pos = strpos($accept, ';');
            if ($pos !== false) $accept = substr($accept, 0, $pos);
            $result[] = strtolower(trim($accept));
        }

        return $result;
    }

    public function acceptTypes() {
        if (isset($this->accept['types'])) return $this->accept['types'];
        return $this->accept['types'] = $this->_getAccept('http_accept');
    }

    public function acceptLang() {
        if (isset($this->accept['lang'])) return $this->accept['lang'];
        return $this->accept['lang'] = $this->_getAccept('http_accept_language');
    }

    public function acceptCharset() {
        if (isset($this->accept['charset'])) return $this->accept['charset'];
        return $this->accept['charset'] = $this->_getAccept('http_accept_charset');
    }

    public function acceptEncoding() {
        if (isset($this->accept['encoding'])) return $this->accept['encoding'];
        return $this->accept['encoding'] = $this->_getAccept('http_accept_encoding');
    }

    public function referer() {
        return server('http_referer');
    }

    public function ip($proxy = false) {
        if (!$proxy) return server('remote_addr');

        // private ip range, ip2long()
        $private = array(
            array(0, 50331647),             // 0.0.0.0, 2.255.255.255
            array(167772160, 184549375),    // 10.0.0.0, 10.255.255.255
            array(2130706432, 2147483647),  // 127.0.0.0, 127.255.255.255
            array(2851995648, 2852061183),  // 169.254.0.0, 169.254.255.255
            array(2886729728, 2887778303),  // 172.16.0.0, 172.31.255.255
            array(3221225984, 3221226239),  // 192.0.2.0, 192.0.2.255
            array(3232235520, 3232301055),  // 192.168.0.0, 192.168.255.255
            array(4294967040, 4294967295),  // 255.255.255.0 255.255.255.255
        );

        $ip = server('http_client_ip') ?: server('http_x_forwarded_for') ?: server('remote_addr') ?: '0.0.0.0';
        $ip_set = array_map('trim', explode(',', $ip));

        // 检查是否私有地址，如果不是就直接返回
        foreach ($ip_set as $ip) {
            $long = ip2long($ip);
            $is_private = false;

            foreach ($private as $m) {
                list($min, $max) = $m;
                if ($long >= $min && $long <= $max) {
                    $is_private = true;
                    break;
                }
            }

            if (!$is_private) return $ip;
        }

        return array_shift($ip);
    }

    static public function instance() {
        return self::$instance ?: (self::$instance = new static);
    }
}
