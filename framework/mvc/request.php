<?php
namespace Lysine\MVC;

use Lysine\HttpError;
use Lysine\Utils\Singleton;

class Request extends Singleton {
    protected $method;

    protected $requestUri;

    public function __get($key) {
        return $this->request($key);
    }

    public function get($key = null, $default = false) {
        return get($key, $default);
    }

    public function post($key = null, $default = false) {
        return post($key, $default);
    }

    public function request($key = null, $default = false) {
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

    public function cookie() {
        return call_user_func_array('cookie', func_get_args());
    }

    public function session() {
        return call_user_func_array('session', func_get_args());
    }

    public function header($key) {
        $skey = 'http_'. str_replace('-', '_', $key);
        $sval = $this->server($skey);
        if ($sval) return $sval;

        return false;
    }

    public function method() {
        if ($this->method) return $this->method;

        $method = $this->header('x-http-method-override');
        // 某些js库的ajax封装使用这种方式
        if (!$method) {
            $method = $this->post('_method');
            // 不知道去掉这个参数是否画蛇添足，应该问题不大
            if ($method) unset($_POST['_method']);
        }

        $this->method = strtolower( $method ? $method : $this->server('request_method') );
        return $this->method;
    }

    public function requestUri() {
        if ($this->requestUri !== null) return $this->requestUri;

        $uri = $this->server('http_x_rewrite_url');
        if ($uri) return $this->requestUri = $uri;

        $uri = $this->server('request_uri');
        if ($uri) return $this->requestUri = $uri;

        $uri = $this->server('orig_path_info');
        if ($uri) {
            $query = $this->server('query_string');
            if (!empty($query)) $uri .= '?'. $query;
            return $this->requestUri = $uri;
        }

        throw new HttpError('Unable to get request URI', 500);
    }

    public function requestBaseUri() {
        $uri = $this->requestUri();
        $pos = strpos($uri, '?');
        if ($pos !== false) $uri = substr($uri, 0, $pos);
        return $uri;
    }

    public function isGET() {
        return $this->method() === 'get';
    }

    public function isPOST() {
        return $this->method() === 'post';
    }

    public function isPUT() {
        return $this->method() === 'put';
    }

    public function isDELETE() {
        return $this->method() === 'delete';
    }

    public function isAJAX() {
        return strtolower($this->header('X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    // copy from qeephp
    public function isFlash() {
        $agent = strtolower($this->header('USER_AGENT'));
        return strpos($agent, 'shockwave flash') !== false || strpos($agent, 'adobeair') !== false;
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
        return $this->server('remote_addr', '0.0.0.0');
    }
}
