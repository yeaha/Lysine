<?php
namespace Lysine {
    use Lysine\DataMapper\Data;
    use Lysine\HttpError;
    use Lysine\MVC\Application;
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

    class HttpError extends Error {
        public function getHeader() {
            $header = array(Response::httpStatus($this->getCode()));
            if (isset($this->header))
                $header = array_merge($header, $this->header);
            return $header;
        }

        static public function bad_request(array $more = array()) {
            return new static('Bad Request', 400, null, $more);
        }

        static public function unauthorized(array $more = array()) {
            return new static('Unauthorized', 401, null, $more);
        }

        static public function payment_required(array $more = array()) {
            return new static('Payment Required', 402, null, $more);
        }

        static public function forbidden(array $more = array()) {
            return new static('Forbidden', 403, null, $more);
        }

        static public function page_not_found(array $more = array()) {
            if (!isset($more['url']))
                $more['url'] = req()->requestUri();
            return new static('Page Not Found', 404, null, $more);
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

            return new static('Method Not Allowed', 405, null, $more);
        }

        static public function not_acceptable(array $more = array()) {
            return new static('Not Acceptable', 406, null, $more);
        }

        static public function request_timeout(array $more = array()) {
            return new static('Request Time-out', 408, null, $more);
        }

        static public function conflict(array $more = array()) {
            return new static('Conflict', 409, null, $more);
        }

        static public function gone(array $more = array()) {
            return new static('Gone', 410, null, $more);
        }

        static public function precondition_failed(array $more = array()) {
            return new static('Precondition Failed', 412, null, $more);
        }

        static public function request_entity_too_large(array $more = array()) {
            return new static('Request Entity Too Large', 413, null, $more);
        }

        static public function unsupported_media_type(array $more = array()) {
            return new static('Unsupported Media Type', 415, null, $more);
        }

        static public function internal_server_error(array $more = array()) {
            return new static('Internal Server Error', 500, null, $more);
        }

        static public function not_implemented(array $more = array()) {
            if (!isset($more['method']))
                $more['method'] = req()->method();
            return new static('Not Implemented', 501, null, $more);
        }

        static public function bad_gateway(array $more = array()) {
            return new static('Bad Gateway', 502, null, $more);
        }

        static public function service_unavailable(array $more = array()) {
            return new static('Service Unavailable', 503, null, $more);
        }

        static public function gateway_timeout(array $more = array()) {
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
            if ($class instanceof Data) $class = get_class($class);
            return new static("{$class} is readonly");
        }

        static public function not_allow_null($class, $prop) {
            if ($class instanceof Data) $class = get_class($class);
            return new static("{$class}: Property {$prop} not allow null");
        }

        static public function refuse_update($class, $prop) {
            if ($class instanceof Data) $class = get_class($class);
            return new static("{$class}: Property {$prop} refuse update");
        }

        static public function undefined_collection($class) {
            if ($class instanceof Data) $class = get_class($class);
            return new static("{$class}: Undefined collection");
        }

        static public function undefined_primarykey($class) {
            if ($class instanceof Data) $class = get_class($class);
            return new static("{$class}: Undefined primary key");
        }

        static public function mismatching_pattern($class, $prop, $pattern) {
            if ($class instanceof Data) $class = get_class($class);
            return new static("{$class}: Property {$prop} mismatching pattern {$pattern}");
        }

        static public function insert_failed(Data $obj, $previous = null, array $more = array()) {
            $class = get_class($obj);
            $more['class'] = $class;
            $more['record'] = $obj->toArray();
            $more['method'] = 'insert';

            return new static("{$class} insert failed", 0, $previous, $more);
        }

        static public function update_failed(Data $obj, $previous = null, array $more = array()) {
            $class = get_class($obj);
            $more['class'] = $class;
            $more['record'] = $obj->toArray();
            $more['method'] = 'update';

            return new static("{$class} update failed", 0, $previous, $more);
        }

        static public function delete_failed(Data $obj, $previous = null, array $more = array()) {
            $class = get_class($obj);
            $more['class'] = $class;
            $more['primary_key'] = $obj->id();
            $more['method'] = 'delete';

            return new static("{$class} delete failed", 0, $previous, $more);
        }
    }

    function autoload($class) {
        if (stripos($class, 'lysine\\') !== 0) return false;

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

    // $terminate = true 处理完后直接结束
    function __on_exception($exception, $terminate = true) {
        $code = $exception instanceof HttpError
              ? $exception->getCode()
              : 500;

        if (PHP_SAPI == 'cli') {
            if (!$terminate) return array($code, array());
            echo $exception;
            die(1);
        }

        $header = $exception instanceof HttpError
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
