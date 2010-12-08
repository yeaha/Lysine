<?php
namespace Lysine\MVC;

use Lysine\HttpError;
use Lysine\Utils\Singleton;

class Request extends Singleton {
    protected $method;

    protected $requestUri;

    protected $accept = array();

    public function __get($key) {
        return $this->request($key);
    }

    public function get($key = null, $default = false) {
        return get($key, $default);
    }

    public function post($key = null, $default = false) {
        return post($key, $default);
    }

    public function put($key = null, $default = false) {
        return put($key, $default);
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
        if ($sval = $this->server($skey)) return $sval;

        return false;
    }

    public function method() {
        if ($this->method) return $this->method;

        if (!$method = $this->header('x-http-method-override')) {
            // 某些js库的ajax封装使用这种方式
            if ($method = $this->post('_method')) unset($_POST['_method']);
        }

        return $this->method = strtolower( $method ?: $this->server('request_method') );
    }

    public function requestUri() {
        if ($this->requestUri !== null) return $this->requestUri;

        if ($uri = $this->server('http_x_rewrite_url')) return $this->requestUri = $uri;

        if ($uri = $this->server('request_uri')) return $this->requestUri = $uri;

        if ($uri = $this->server('orig_path_info')) {
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
        return ($this->method() === 'get') ?: $this->isHEAD();
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

    public function isHEAD() {
        return $this->method() === 'head';
    }

    public function isAJAX() {
        return strtolower($this->header('X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    // copy from qeephp
    public function isFlash() {
        $agent = strtolower($this->header('USER_AGENT'));
        return (strpos($agent, 'shockwave flash') !== false) || (strpos($agent, 'adobeair') !== false);
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
        return $this->server('http_referer');
    }

    public function ip() {
        $ip = $this->server('http_x_forwarded_for') ?: $this->server('remote_addr');
        if (!function_exists('filter_var')) return $ip;

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ?: '0.0.0.0';
    }
}
