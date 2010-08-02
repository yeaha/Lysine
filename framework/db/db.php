<?php
namespace Lysine {
    class Db {
        const TYPE_INTEGER = 1;
        const TYPE_FLOAT = 2;
        const TYPE_BOOL = 3;
        const TYPE_STRING = 4;
        const TYPE_BINARY = 5;

        static public function factory($dsn, $user, $pass, array $options = array()) {
            if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
                throw new \InvalidArgumentException('Invalid dsn');

            $adapter = $match[1];

            $class = __NAMESPACE__ .'\Db\Adapter\\'. ucfirst($adapter);
            return new $class($dsn, $user, $pass, $options);
        }
    }
}

namespace Lysine\Db {
    /**
     * 数据库sql执行异常
     *
     * @package DB
     * @author yangyi <yangyi.cn.gz@gmail.com>
     */
    class Exception extends \Exception {
        /**
         * 数据库原生错误代码
         *
         * @var mixed
         * @access protected
         */
        protected $native_code;

        public function __construct($message = '', $code = 0, $previous = null, $native_code = null) {
            parent::__construct($message, $code, $previous);
            $this->native_code = $native_code;
        }

        /**
         * 获得数据库原生错误代码
         *
         * @access public
         * @return void
         */
        public function getNativeCode() {
            return $this->native_code;
        }
    }
}
