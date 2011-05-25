<?php
namespace Lysine\MVC;

use Lysine\Utils\Singleton;

/**
 * http返回数据封装
 *
 * @package MVC
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Response extends Singleton {
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

    public function setContentType($type) {
        if (is_array($type)) $type = implode(',', $type);
        $this->setHeader('Content-Type', $type);
        return $this;
    }

    public function sendHeader($only_header = false) {
        if (is_integer($this->code) && $this->code != 200) {
            if ($status = self::httpStatus($this->code))
                header($status);
        }

        foreach ($this->header as $name => $value) {
            $header = ($value === null)
                    ? $name
                    : $name .': '. $value;
            header($header);
        }
        $this->header = array();

        if ($only_header) return $this;

        if ($this->session) {
            if (!isset($_SESSION)) session_start();

            foreach ($this->session as $sess) {
                list($path, $val) = $sess;
                array_set($_SESSION, $path, $val);
            }

            $this->session = array();
        }

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
}
