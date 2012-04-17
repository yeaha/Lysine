<?php
namespace Lysine {
    use Lysine\HTTP;
    use Lysine\MVC\Response;

    defined('DEBUG') or define('DEBUG', false);
    require __DIR__ .'/functions.php';

    if (!defined('LYSINE_NO_EXCEPTION_HANDLER'))
        set_exception_handler('\Lysine\__on_exception');

    if (!defined('LYSINE_NO_ERROR_HANDLER'))
        set_error_handler('\Lysine\__on_error');

    spl_autoload_register('\Lysine\autoload');

    class Config {
        static protected $config = array();

        static public function import(array $config) {
            self::$config = array_merge(self::$config, $config);
        }

        static public function set() {
            $path = func_get_args();
            $val = array_pop($path);
            return \Lysine\array_set(self::$config, $path, $val);
        }

        static public function get($path) {
            $path = is_array($path) ? $path : func_get_args();
            return $path ? \Lysine\array_get(self::$config, $path) : self::$config;
        }
    }

    class Error extends \Exception {
        private $more = array();

        public function __construct($message, $code = 0, \Exception $previous = null, array $more = array()) {
            if (isset($more['message'])) {
                $message = $more['message'];
                unset($more['message']);
            }

            $this->more = $more;
            parent::__construct($message, $code, $previous);
        }

        public function __get($key) {
            return array_key_exists($key, $this->more) ? $this->more[$key] : false;
        }

        public function __set($key, $val) {
            $this->more[$key] = $val;
        }

        public function __isset($key) {
            return array_key_exists($key, $this->more);
        }

        public function toArray() {
            $result = $this->more;
            $result['message'] = $this->getMessage();
            $result['code'] = $this->getCode();

            if ($previous = $this->getPrevious()) {
                if ($previous instanceof Error) {
                    $result['previous'] = $previous->toArray();
                } else {
                    $result['previous']['message'] = $previous->getMessage();
                    $result['previous']['code'] = $previous->getCode();
                }
            }

            return $result;
        }

        public function getMore($with_previous = false) {
            $more = $this->more;
            if ($with_previous && ($previous = $this->getPrevious()) && $previous instanceof Error)
                $more['__previous__'] = $previous->getMore();
            return $more;
        }

        static public function invalid_argument($function, $class = null) {
            if ($class) $function = "{$class}::{$function}";
            return new static("Invalid argument of {$function}");
        }

        static public function call_undefined($function, $class = null) {
            if ($class) $function = "{$class}::{$function}";
            return new static("Call to undefined {$function}");
        }

        static public function undefined_property($class, $property) {
            if (is_object($class)) $class = get_class($class);
            return new static("Undefined property {$property} of {$class}");
        }

        static public function not_callable($function) {
            return new static("{$function} is not callable");
        }

        static public function file_not_found($file) {
            return new static("{$file} is not exist or readable");
        }

        static public function require_extension($extension) {
            return new static("Require {$extension} extension");
        }
    }

    function autoload($class) {
        if (stripos($class, 'lysine\\') !== 0) return false;

        static $files = null;
        if ($files === null)
            $files = require __DIR__ . '/class_files.php';

        $class = strtolower(ltrim($class, '\\'));

        if (!array_key_exists($class, $files)) return false;
        $file = __DIR__ .'/'. $files[$class];

        require $file;
        return class_exists($class, false) || interface_exists($class, false);
    }

    function logger($domain = null) {
        $name = '__LYSINE__';
        if ($domain) $name .= '.'. strtoupper($domain);
        return \Lysine\Utils\Logging::getLogger($name);
    }

    // $terminate = true 处理完后直接结束
    function __on_exception($exception, $terminate = true) {
        $code = $exception instanceof HTTP\Error
              ? $exception->getCode()
              : 500;

        if (PHP_SAPI == 'cli') {
            if (!$terminate) return array($code, array());
            echo $exception;
            die(1);
        }

        $header = $exception instanceof HTTP\Error
                ? $exception->getHeader()
                : array(Response::httpStatus(500));

        if (DEBUG) {
            $message = strip_tags($exception->getMessage());
            if (strpos($message, "\n") !== false) {
                $lines = explode("\n", $message);
                $message = $lines[0];
            }
            $header[] = 'X-Exception-Message: '. $message;
            $header[] = 'X-Exception-Code: '. $exception->getCode();

            foreach (explode("\n", $exception->getTraceAsString()) as $index => $line)
                $header[] = sprintf('X-Exception-Trace-%d: %s', $index, $line);
        }

        if ($terminate && !headers_sent())
            foreach ($header as $h) header($h);

        return array($code, $header);
    }

    function __on_error($code, $message, $file = null, $line = null) {
        if (error_reporting() & $code)
            throw new \ErrorException($message, $code, 0, $file, $line);
        return true;
    }

    // 为兼容性保留，将会被废除
    class HttpError extends \Lysine\HTTP\Error {
    }
}

namespace Lysine\HTTP {
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NO_CONTENT = 204;
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const SEE_OTHER = 303;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const PAYMENT_REQUIRED = 402;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTABLE = 406;
    const REQUEST_TIMEOUT = 408;
    const CONFLICT = 409;
    const GONE = 410;
    const LENGTH_REQUIRED = 411;
    const PRECONDITION_FAILED = 412;
    const REQUEST_ENTITY_TOO_LARGE = 413;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const EXPECTATION_FAILED = 417;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;
}

namespace Lysine\MVC {
    // 路由事件
    const BEFORE_DISPATCH_EVENT = 'before dispatch';
    const AFTER_DISPATCH_EVENT = 'after dispatch';
}

namespace Lysine\Storage\DB {
    const CONNECT_EVENT = 'connect event';
    const INSERT_EVENT = 'insert event';
    const UPDATE_EVENT = 'update event';
    const DELETE_EVENT = 'delete event';
    const EXECUTE_EVENT = 'execute event';
    const EXECUTE_EXCEPTION_EVENT = 'execute exception event';
}
