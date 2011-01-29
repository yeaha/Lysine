<?php
namespace Lysine {
    use Lysine\ORM;
    use Lysine\MVC\Response;
    use Lysine\HttpError;

    defined('DEBUG') or define('DEBUG', false);

    class Config {
        static protected $config = array();

        static public function import(array $config) {
            self::$config = array_merge(self::$config, $config);
        }

        static public function set() {
            $path = func_get_args();
            $val = array_pop($path);
            return array_set(self::$config, $path, $val);
        }

        static public function get($path) {
            $path = is_array($path) ? $path : func_get_args();
            return $path ? array_get(self::$config, $path) : self::$config;
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

        public function getMore() {
            return $this->more;
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

    class HttpError extends Error {
        public function getHeader() {
            return Response::httpStatus($this->getCode());
        }

        static public function bad_request(array $more) {
            return new static('Bad Request', 400, null, $more);
        }

        static public function unauthorized(array $more) {
            return new static('Unauthorized', 401, null, $more);
        }

        static public function forbidden(array $more) {
            return new static('Forbidden', 403, null, $more);
        }

        static public function page_not_found($url, $more = array()) {
            $more['url'] = $url;
            return new static('Page Not Found', 404, null, $more);
        }

        static public function method_not_allowed($method, array $more = array()) {
            $more['method'] = $method;
            return new static('Method Not Allowed', 405, null, $more);
        }

        static public function not_acceptable(array $more) {
            return new static('Not Acceptable', 406, null, $more);
        }

        static public function request_timeout(array $more) {
            return new static('Request Time-out', 408, null, $more);
        }

        static public function conflict(array $more) {
            return new static('Conflict', 409, null, $more);
        }

        static public function gone(array $more) {
            return new static('Gone', 410, null, $more);
        }

        static public function precondition_failed(array $more) {
            return new static('Precondition Failed', 412, null, $more);
        }

        static public function request_entity_too_large(array $more) {
            return new static('Request Entity Too Large', 413, null, $more);
        }

        static public function unsupported_media_type(array $more) {
            return new static('Unsupported Media Type', 415, null, $more);
        }

        static public function internal_server_error(array $more) {
            return new static('Internal Server Error', 500, null, $more);
        }

        static public function not_implemented($method, array $more = array()) {
            $more['method'] = $method;
            return new static('Not Implemented', 501, null, $more);
        }

        static public function bad_gateway(array $more) {
            return new static('Bad Gateway', 502, null, $more);
        }

        static public function service_unavailable(array $more) {
            return new static('Service Unavailable', 503, null, $more);
        }

        static public function gateway_timeout(array $more) {
            return new static('Gateway Time-out', 504, null, $more);
        }
    }

    class StorageError extends Error {
        static public function undefined_storage($storage_name) {
            return new static('Undefined storage service:'. $storage_name);
        }

        static public function connect_failed($storage_name) {
            return new static("Connect failed! Storage service: {$storage_name}");
        }
    }

    class OrmError extends StorageError {
        static public function readonly($class) {
            if ($class instanceof ORM) $class = get_class($class);
            return new static("{$class} is readonly");
        }

        static public function not_allow_empty($class, $prop) {
            if ($class instanceof ORM) $class = get_class($class);
            return new static("{$class}: Property {$prop} not allow empty");
        }

        static public function refuse_update($class, $prop) {
            if ($class instanceof ORM) $class = get_class($class);
            return new static("{$class}: Property {$prop} refuse update");
        }

        static public function undefined_collection($class) {
            if ($class instanceof ORM) $class = get_class($class);
            return new static("{$class}: Undefined collection");
        }

        static public function undefined_primarykey($class) {
            if ($class instanceof ORM) $class = get_class($class);
            return new static("{$class}: Undefined primary key");
        }

        static public function insert_failed(ORM $obj, $previous = null, array $more = array()) {
            $class = get_class($obj);
            $more['class'] = $class;
            $more['record'] = $obj->toArray();
            $more['method'] = 'insert';

            return new static("{$class} insert failed", 0, $previous, $more);
        }

        static public function update_failed(ORM $obj, $previous = null, array $more = array()) {
            $class = get_class($obj);
            $more['class'] = $class;
            $more['record'] = $obj->toArray();
            $more['method'] = 'update';

            return new static("{$class} update failed", 0, $previous, $more);
        }

        static public function delete_failed(ORM $obj, $previous = null, array $more = array()) {
            $class = get_class($obj);
            $more['class'] = $class;
            $more['primary_key'] = $obj->id();
            $more['method'] = 'delete';

            return new static("{$class} delete failed", 0, $previous, $more);
        }
    }

    function autoload($class) {
        if (!in_namespace($class, 'Lysine')) return false;

        static $files = null;
        if ($files === null)
            $files = array_change_key_case(require __DIR__ . '/class_files.php');

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

    spl_autoload_register('Lysine\autoload');
    require __DIR__ .'/functions.php';

    // $terminate = true 处理完后直接结束
    function __on_exception($exception, $terminate = true) {
        if (DEBUG) {
            try {
                \Lysine\logger()->exception($exception, 8);
            } catch (\Exception $ex) {
            }
        }

        $code = $exception instanceof HttpError
              ? $exception->getCode()
              : 500;

        if (PHP_SAPI == 'cli') {
            if (!$terminate) return array($code, array());
            echo $exception;
            die(1);
        }

        $header = array(
            Response::httpStatus($code) ?: Response::httpStatus(500),
        );

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
    if (!defined('LYSINE_NO_EXCEPTION_HANDLER'))
        set_exception_handler('\Lysine\__on_exception');

    function __on_error($code, $message, $file = null, $line = null) {
        if (error_reporting() && $code)
            throw new \ErrorException($message, $code, 0, $file, $line);
        return true;
    }
    if (!defined('LYSINE_NO_ERROR_HANDLER'))
        set_error_handler('\Lysine\__on_error');
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
