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
     * 数据库连接类接口
     * 实现了此接口就可以在Lysine内涉及数据库操作的类里通用
     *
     * @package DB
     * @author yangyi <yangyi.cn.gz@gmail.com>
     */
    interface IAdapter {
        /**
         * 构造函数
         *
         * @param string $dsn
         * @param string $user
         * @param string $pass
         * @param array $options
         * @access public
         * @return void
         */
        public function __construct($dsn, $user, $pass, array $options = array());

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
         * 如果此adapter要使用Lysine db select和active record
         * 就必须返回IResult才行
         *
         * @param string $sql
         * @param mixed $bind
         * @access public
         * @return Lysine\Db\IResult
         */
        public function execute($sql, $bind = null);

        /**
         * 生成查询助手类
         *
         * @param string $table_name
         * @access public
         * @return Lysine\Db\Select
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
        public function update($table_name, array $row, $where = null, $bind = null);

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
        public function delete($table_name, $where = null, $bind = null);

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
