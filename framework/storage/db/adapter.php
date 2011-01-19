<?php
namespace Lysine\Storage\DB;

use Lysine\StorageError;
use Lysine\Storage\DB\IAdapter;
use Lysine\Storage\DB\Expr;
use Lysine\Storage\DB\Select;

/**
 * PDO数据库连接
 * 对pdo连接对象加了一些装饰方法
 *
 * @uses IAdapter
 * @abstract
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class Adapter implements IAdapter {
    /**
     * 数据库连接配置
     *
     * @var array
     * @access private
     */
    private $config;

    /**
     * pdo连接对象
     *
     * @var PDO
     * @access protected
     */
    protected $dbh;

    /**
     * 是否处于事务中
     *
     * @var boolean
     * @access protected
     */
    protected $in_transaction = false;

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
    public function __construct(array $config) {
        $explode = explode('\\', get_class($this));
        $extension = 'pdo_'. strtolower( array_pop($explode) );

        if (!extension_loaded($extension))
            throw new \RuntimeException('Need '. $extension .' extension!');

        $this->config = static::parseConfig($config);
    }

    /**
     * 析构函数
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        if ($this->isConnected())
            while ($this->in_transaction) $this->rollback();
    }

    /**
     * 魔法方法
     * 直接调用PDO连接对象
     *
     * @param string $fn
     * @param array $args
     * @access public
     * @return mixed
     */
    public function __call($fn, $args) {
        $this->connect();

        if (method_exists($this->dbh, $fn))
            return call_user_func_array(array($this->dbh, $fn), $args);

        throw StorageError::call_undefined($fn, get_class($this));
    }

    /**
     * 魔法方法，函数式调用
     *
     * $rowset = $db('select * from users')->getAll();
     *
     * @access public
     * @return mixed
     */
    public function __invoke() {
        $args = func_get_args();
        return call_user_func_array(array($this, 'execute'), $args);
    }

    /**
     * 魔法方法
     *
     * @access public
     * @return void
     */
    public function __sleep() {
        $this->disconnect();
    }

    /**
     * 是否已经连接数据库
     *
     * @access protected
     * @return boolean
     */
    protected function isConnected() {
        return $this->dbh instanceof \PDO;
    }

    /**
     * 连接数据库
     *
     * @access protected
     * @return self
     */
    protected function connect() {
        if ($this->isConnected()) return $this;

        list($dsn, $user, $pass, $options) = $this->config;

        if (!isset($options[\PDO::ATTR_ERRMODE]))
            // 出错时抛出异常
            $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        if (!isset($options[\PDO::ATTR_STATEMENT_CLASS]))
            // 可以定义自己的result class
            $options[\PDO::ATTR_STATEMENT_CLASS] = array('\Lysine\Storage\DB\Result');

        $dbh = new \PDO($dsn, $user, $pass, $options);

        fire_event($this, CONNECT_EVENT, $this);

        $this->dbh = $dbh;
        return $this;
    }

    /**
     * 断开连接
     *
     * @access protected
     * @return void
     */
    protected function disconnect() {
        $this->dbh = null;
    }

    /**
     * 返回pdo连接对象
     *
     * @access public
     * @return void
     */
    public function getHandle() {
        $this->connect();
        return $this->dbh;
    }

    /**
     * 开始事务
     *
     * @access public
     * @return boolean
     */
    public function begin() {
        $this->connect();
        if ($begin = $this->dbh->beginTransaction())
            $this->in_transaction = true;
        return $begin;
    }

    /**
     * 回滚事务
     *
     * @access public
     * @return boolean
     */
    public function rollback() {
        if (!$this->in_transaction || !$this->isConnected()) return false;
        if ($rollback = $this->dbh->rollBack())
            $this->in_transaction = false;
        return $rollback;
    }

    /**
     * 提交事务
     *
     * @access public
     * @return boolean
     */
    public function commit() {
        if (!$this->in_transaction || !$this->isConnected()) return false;
        if ($commit = $this->dbh->commit())
            $this->in_transaction = false;
        return $commit;
    }

    /**
     * 是否处于事务中
     *
     * @access public
     * @return boolean
     */
    public function inTransaction() {
        return $this->in_transaction;
    }

    /**
     * 执行sql并返回结果或结果对象
     *
     * @param string $sql
     * @param mixed $bind
     * @access public
     * @return Lysine\Storage\DB\IResult
     */
    public function execute($sql, $bind = null) {
        if ($bind === null) $bind = array();
        if (!is_array($bind)) $bind = array_slice(func_get_args(), 1);

        try {
            $this->connect();
            $sth = ($sql instanceof \PDOStatement)
                 ? $sql
                 : $this->dbh->prepare($sql);
            if ($sth === false) return false;

            if (!$sth->execute($bind)) return false;
        } catch (\PDOException $ex) {
            $error = new StorageError($ex->getMessage(), $ex->errorInfo[1], $ex, array(
                'sql' => (string)$sql,
                'bind' => $bind,
                'native_code' => $ex->errorInfo[0]
            ));
            fire_event($this, EXECUTE_EXCEPTION_EVENT, $error);
            throw $error;
        }

        fire_event($this, EXECUTE_EVENT, array($sql, $bind));
        if (DEBUG) {
            $log = 'Execute SQL: '. $sql;
            if ($bind) $log .= ' with '. json_encode($bind);
            \Lysine\logger('storage')->debug($log);
        }

        $sth->setFetchMode(\PDO::FETCH_ASSOC);
        return $sth;
    }

    /**
     * 生成Lysine\Storage\DB\Select实例
     *
     * @param string $table_name
     * @access public
     * @return Lysine\Storage\DB\Select
     */
    public function select($table_name) {
        $select = new Select($this);
        return $select->from($table_name);
    }

    /**
     * 插入记录
     *
     * @param string $table
     * @param array $row
     * @param boolean $return_prepare
     * @access public
     * @return integer
     */
    public function insert($table, array $row, $return_prepare = false) {
        $bind = $cols = $vals = array();
        foreach ($row as $col => $val) {
            $cols[] = $col;
            // 避免字符逃逸处理
            // Expr数据直接放到生成的sql中，不通过占位符方式传递
            if ($val instanceof Expr) {
                $vals[] = $val;
            } else {
                $holder = ':'. $col;
                $vals[] = $holder;
                $bind[$holder] = $val;
            }
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->qtab($table),
            implode(',', $this->qcol($cols)),
            implode(',', $vals));

        if (!$sth = $this->prepare($sql)) return false;
        if ($return_prepare) return $sth;
        if (!$this->execute($sth, $bind)) return false;

        if ($affected = $sth->rowCount())
            fire_event($this, INSERT_EVENT, $this, $table, $affected);

        return $affected;
    }

    /**
     * 更新记录
     *
     * $adapter->update('users', array('passwd' => 'abc'), 'id = ?', 1);
     * $adapter->update('users', array('passwd' => 'abc'), array('id = ?', 1));
     * $adapter->update('table', array('passwd' => 'abc'), 'id = :id', array(':id' => 1));
     * $adapter->update('table', array('passwd' => 'abc'), 'id = :id', 1);
     *
     * @param string $table
     * @param array $row
     * @param mixed $where
     * @param mixed $bind
     * @access public
     * @return integer
     */
    public function update($table, array $row, $where, $bind = null) {
        // 返回prepare后的结果
        $return_prepare = false;

        // 先解析where
        if (is_array($where)) {
            $return_prepare = (bool)$bind;
            list($where, $where_bind) = call_user_func_array(array($this, 'parsePlaceHolder'), $where);
        } else {
            $args = func_get_args();
            list($where, $where_bind) = call_user_func_array(array($this, 'parsePlaceHolder'), array_slice($args, 2));
        }

        //检查place holder类型
        $holder = null;
        if ($where_bind AND !is_assoc_array($where_bind)) $holder = '?';

        $set = $bind = array();
        foreach ($row as $col => $val) {
            if ($val instanceof Expr) {
                $set[] = $this->qcol($col) .' = '. $val;
                continue;
            }

            $holder_here = $holder ?: ':'. $col;
            $set[] = $this->qcol($col) .' = '. $holder_here;

            if ($holder_here == '?') {
                $bind[] = $val;
            } else {
                $bind[$holder_here] = $val;
            }
        }
        $bind = array_merge($bind, $where_bind);

        $sql = sprintf('UPDATE %s SET %s', $this->qtab($table), implode(',', $set));
        if ($where) $sql .= ' WHERE '. $where;

        if (!$sth = $this->prepare($sql)) return false;
        if ($return_prepare) return $sth;
        if (!$this->execute($sth, $bind)) return false;

        if ($affected = $sth->rowCount())
            fire_event($this, UPDATE_EVENT, $this, $table, $affected);

        return $affected;
    }

    /**
     * 删除记录
     *
     * $adapter->delete('users', 'id = ?', 3);
     * $adapter->delete('users', array('id = ?', 3));
     * $adapter->delete('table', 'a = ? and b = ?', 'a1', 'b1');
     * $adapter->delete('table', 'a = :a and b = :b', array(':a' => 'a1', ':b' => 'b1'));
     * $adapter->delete('table', 'a = :a and b = :b', 'a1', 'b1');
     *
     * @param string $table
     * @param mixed $where
     * @param mixed $bind
     * @access public
     * @return integer
     */
    public function delete($table, $where, $bind = null) {
        $sql = 'DELETE FROM '. $this->qtab($table);
        if (is_array($where)) {
            list($where, $bind) = call_user_func_array(array($this, 'parsePlaceHolder'), $where);
        } else {
            $args = func_get_args();
            list($where, $bind) = call_user_func_array(array($this, 'parsePlaceHolder'), array_slice($args, 1));
        }

        if ($where) $sql .= ' WHERE '. $where;

        if (!$sth = $this->execute($sql, $bind)) return false;

        if ($affected = $sth->rowCount())
            fire_event($this, DELETE_EVENT, $this, $table, $affected);

        return $affected;
    }

    /**
     * 逃逸特殊字符处理
     *
     * @param mixed $val
     * @access public
     * @return mixed
     */
    public function qstr($val) {
        if (is_array($val)) {
            foreach ($val as &$v) $v = $this->qstr($v);
            return $val;
        }

        if ($val instanceof Expr) return $val;
        if (is_numeric($val)) return $val;
        if ($val === null) return 'NULL';

        $this->connect();
        return $this->dbh->quote($val);
    }

    /**
     * 解析占位符及参数
     * 'user = :user', 'username'
     * 'user = :user', array(':user' => 'username')
     * 'user = ?', 'username'
     * 'user = ?', array('username')
     *
     * @param string $sql
     * @param mixed $bind
     * @access public
     * @return array
     */
    public function parsePlaceHolder($sql, $bind = null) {
        if ($bind === null) return array($sql, array());

        $bind = is_array($bind) ? $bind : array_slice(func_get_args(), 1);

        if (!preg_match_all('/[^:]+(:[a-z0-9_\-]+)/i', $sql, $match))
            return array($sql, array_values($bind));

        $place = $match[1];
        if (count($place) != count($bind))
            throw new \UnexpectedValueException('Missing sql statement parameter');

        return array($sql, array_combine($place, $bind));
    }

    /**
     * 解析pdo adapter配置信息
     *
     * @param array $config
     * @static
     * @access public
     * @return array
     */
    static public function parseConfig(array $config) {
        if (!isset($config['dsn']))
            throw new \InvalidArgumentException('Invalid database config, need "dsn" key');

        $dsn = $config['dsn'];

        $user = isset($config['user']) ? $config['user'] : null;
        $pass = isset($config['pass']) ? $config['pass'] : null;
        $options = (isset($config['options']) AND is_array($config['options']))
                 ? $config['options']
                 : array();

        return array($dsn, $user, $pass, $options);
    }
}
