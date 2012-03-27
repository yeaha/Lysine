<?php
namespace Lysine {
    use Lysine\HTTP;
    use Lysine\MVC\Response;

    defined('DEBUG') or define('DEBUG', false);
    require __DIR__ .'/functions.php';

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
    spl_autoload_register('Lysine\autoload');

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

namespace Lysine\HTTP {
    use Lysine\MVC\Application;
    use Lysine\MVC\Response;

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

namespace Lysine\Storage {
    class Error extends \Lysine\Error {
        static public function undefined_storage($storage_name) {
            return new static('Undefined storage service:'. $storage_name);
        }

        static public function connect_failed($storage_name) {
            return new static("Connect failed! Storage service: {$storage_name}");
        }
    }
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

namespace Lysine {
    /**
     * 存储服务
     *
     * @package Storage
     * @author yangyi <yangyi.cn.gz@gmail.com>
     */
    interface IStorage {
        /**
         * 构造函数
         * 参数都必须以数组方式传递
         * 这样才可以让Lysine\Storage\Manager使用统一的初始化方法
         *
         * @param array $config
         * @access public
         * @return void
         */
        public function __construct(array $config);
    }
}

namespace Lysine\Storage\DB {
    /**
     * 数据库连接类接口
     * 实现了此接口就可以在Lysine内涉及数据库操作的类里通用
     *
     * @package DB
     * @author yangyi <yangyi.cn.gz@gmail.com>
     */
    interface IAdapter extends \Lysine\IStorage {
        /**
         * 返回实际的数据库连接句柄
         *
         * @access public
         * @return mixed
         */
        public function getHandle();

        /**
         * 开始事务
         *
         * @access public
         * @return void
         */
        public function begin();

        /**
         * 回滚事务
         *
         * @access public
         * @return void
         */
        public function rollback();

        /**
         * 提交事务
         *
         * @access public
         * @return void
         */
        public function commit();

        /**
         * 执行sql语句并返回IResult实例
         * 就必须返回IResult才行
         *
         * @param string $sql
         * @param mixed $bind
         * @access public
         * @return Lysine\Storage\DB\IResult
         */
        public function execute($sql, $bind = null);

        /**
         * 生成查询助手类
         *
         * @param string $table_name
         * @access public
         * @return Lysine\Storage\DB\Select
         */
        public function select($table_name);

        /**
         * 插入一条数据到指定的表
         * 返回affected row count
         *
         * @param string $table_name
         * @param array $row
         * @access public
         * @return integer
         */
        public function insert($table_name, array $row);

        /**
         * 根据条件更新指定的表
         * 返回affected row count
         *
         * @param string $table_name
         * @param array $row
         * @param string $where
         * @param mixed $bind
         * @access public
         * @return integer
         */
        public function update($table_name, array $row, $where, $bind = null);

        /**
         * 根据条件删除指定的数据
         * 返回affected row count
         *
         * @param string $table_name
         * @param string $where
         * @param mixed $bind
         * @access public
         * @return integer
         */
        public function delete($table_name, $where, $bind = null);

        /**
         * 获得表名字的完全限定名
         *
         * @param string $table_name
         * @access public
         * @return string
         */
        public function qtab($table_name);

        /**
         * 获得字段名字的完全限定名
         *
         * @param string $column_name
         * @access public
         * @return string
         */
        public function qcol($column_name);

        /**
         * 对数据进行安全逃逸处理
         *
         * @param mixed $val
         * @access public
         * @return mixed
         */
        public function qstr($val);

        /**
         * 获得指定表的指定字段最后一次自增长的值
         *
         * @param string $table_name
         * @param string $column
         * @access public
         * @return integer
         */
        public function lastId($table_name = null, $column = null);
    }

    /**
     * 数据库查询结果类接口
     * 这个类不应该被程序直接掉用
     * 应该是被IAdapter execute()方法生成
     *
     * @package DB
     * @author yangyi <yangyi.cn.gz@gmail.com>
     */
    interface IResult {
        /**
         * 获得一行数据
         *
         * @access public
         * @return array
         */
        public function getRow();

        /**
         * 获得指定列的数据
         *
         * @param int $col_number
         * @access public
         * @return mixed
         */
        public function getCol($col_number = 0);

        /**
         * 获得指定列的所有数据
         *
         * @param int $col_number
         * @access public
         * @return array
         */
        public function getCols($col_number = 0);

        /**
         * 获得所有数据
         *
         * @param string $col
         * @access public
         * @return array
         */
        public function getAll($col = null);
    }
}

namespace Lysine\Utils\Logging {
    interface IHandler {
        public function emit(array $record);
    }
}

namespace Lysine {
    // 为兼容性保留，将会被废除
    class HttpError extends \Lysine\HTTP\Error {
    }

    // 为兼容性保留，将会被废除
    class StorageError extends \Lysine\Storage\Error {
    }
}
