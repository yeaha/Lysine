<?php
namespace Lysine\MVC {
    use Lysine\HTTP;

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

            throw new HTTP\Error('Unable to get request URI', 500);
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

            return array_shift($ip_set);
        }

        static public function instance() {
            return self::$instance ?: (self::$instance = new static);
        }
    }

    /**
     * http返回数据封装
     *
     * @package MVC
     * @author yangyi <yangyi.cn.gz@gmail.com>
     */
    class Response {
        static private $instance;

        static protected $status = array(
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

        protected $code = 200;

        protected $header = array();

        protected $session = array();

        protected $cookie = array();

        protected $body;

        public function reset() {
            $this->code = 200;
            $this->header = $this->cookie = $this->session = array();
            $this->body = null;
            return $this;
        }

        public function setCode($code) {
            $this->code = (int)$code;
            return $this;
        }

        public function getCode() {
            return $this->code;
        }

        public function setCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = true) {
            $this->cookie[$name] = array($value, $expire, $path, $domain, $secure, $httponly);
            return $this;
        }

        public function setSession($name, $val) {
            if (is_array($name)) {
                $path = $name;
            } else {
                $path = func_get_args();
                $val = array_pop($path);
            }

            $this->session[] = array($path, $val);
            return true;
        }

        public function setHeader($name, $val = null) {
            $this->header[$name] = $val;
            return $this;
        }

        public function sendHeader() {
            // session必须要先于header处理
            // 否则会覆盖header内对于Cache-Control的处理
            if ($this->session) {
                if (!isset($_SESSION)) session_start();

                foreach ($this->session as $sess) {
                    list($path, $val) = $sess;
                    \Lysine\array_set($_SESSION, $path, $val);
                }

                $this->session = array();
            }

            // http 状态
            if ($status = self::httpStatus($this->code))
                header($status);

            // 自定义http header
            foreach ($this->header as $name => $value) {
                $header = ($value === null)
                        ? $name
                        : $name .': '. $value;
                header($header);
            }
            $this->header = array();

            // cookie
            foreach ($this->cookie as $name => $config) {
                list($value, $expire, $path, $domain, $secure, $httponly) = $config;
                setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
            }
            $this->cookie = array();

            return $this;
        }

        public function setBody($body) {
            $this->body = $body;
            return $this;
        }

        public function __toString() {
            // head方法不需要向客户端返回结果
            if (req()->isHEAD()) return '';
            if (in_array($this->code, array(204, 301, 302, 303, 304))) return '';

            return (string)$this->body;
        }

        static public function httpStatus($code) {
            return isset(self::$status[$code]) ? self::$status[$code] : false;
        }

        static public function instance() {
            return self::$instance ?: (self::$instance = new static);
        }
    }
}

namespace Lysine\HTTP {
    use Lysine\MVC\Application;
    use Lysine\MVC\Response;

    class Error extends \Lysine\Error {
        public function getHeader() {
            $header = array(Response::httpStatus($this->getCode()));
            if (isset($this->header))
                $header = array_merge($header, $this->header);
            return $header;
        }

        static public function bad_request(array $more = array()) {
            return new static('Bad Request', BAD_REQUEST, null, $more);
        }

        static public function unauthorized(array $more = array()) {
            return new static('Unauthorized', UNAUTHORIZED, null, $more);
        }

        static public function payment_required(array $more = array()) {
            return new static('Payment Required', PAYMENT_REQUIRED, null, $more);
        }

        static public function forbidden(array $more = array()) {
            return new static('Forbidden', FORBIDDEN, null, $more);
        }

        static public function page_not_found(array $more = array()) {
            if (!isset($more['url']))
                $more['url'] = req()->requestUri();
            return new static('Page Not Found', NOT_FOUND, null, $more);
        }

        static public function method_not_allowed(array $more = array()) {
            if (!isset($more['method']))
                $more['method'] = req()->method();

            if (isset($more['class'])) {
                $class_method = get_class_methods($more['class']);
                $support_method = Application::$support_method;

                if ($allow = array_intersect(array_map('strtoupper', $class_method), $support_method))
                    $more['header'] = array('Allow: '. implode(', ', $allow));
            }

            return new static('Method Not Allowed', METHOD_NOT_ALLOWED, null, $more);
        }

        static public function not_acceptable(array $more = array()) {
            return new static('Not Acceptable', NOT_ACCEPTABLE, null, $more);
        }

        static public function request_timeout(array $more = array()) {
            return new static('Request Time-out', REQUEST_TIMEOUT, null, $more);
        }

        static public function conflict(array $more = array()) {
            return new static('Conflict', CONFLICT, null, $more);
        }

        static public function gone(array $more = array()) {
            return new static('Gone', GONE, null, $more);
        }

        static public function precondition_failed(array $more = array()) {
            return new static('Precondition Failed', PRECONDITION_FAILED, null, $more);
        }

        static public function request_entity_too_large(array $more = array()) {
            return new static('Request Entity Too Large', REQUEST_ENTITY_TOO_LARGE, null, $more);
        }

        static public function unsupported_media_type(array $more = array()) {
            return new static('Unsupported Media Type', UNSUPPORTED_MEDIA_TYPE, null, $more);
        }

        static public function internal_server_error(array $more = array()) {
            return new static('Internal Server Error', INTERNAL_SERVER_ERROR, null, $more);
        }

        static public function not_implemented(array $more = array()) {
            if (!isset($more['method']))
                $more['method'] = req()->method();
            return new static('Not Implemented', NOT_IMPLEMENTED, null, $more);
        }

        static public function bad_gateway(array $more = array()) {
            return new static('Bad Gateway', BAD_GATEWAY, null, $more);
        }

        static public function service_unavailable(array $more = array()) {
            return new static('Service Unavailable', SERVICE_UNAVAILABLE, null, $more);
        }

        static public function gateway_timeout(array $more = array()) {
            return new static('Gateway Time-out', GATEWAY_TIMEOUT, null, $more);
        }
    }
}
