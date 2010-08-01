<?php
namespace Lysine\Db\Adapter;

use Lysine\Db;
use Lysine\Db\IAdapter;
use Lysine\Db\Select;

abstract class Pdo implements IAdapter {
    /**
     * 数据库连接配置
     *
     * @var mixed
     * @access protected
     */
    protected $cfg;

    /**
     * pdo连接对象
     *
     * @var PDO
     * @access protected
     */
    protected $dbh;

    public function __construct(array $config) {
        $explode = explode('\\', get_class($this));
        $extension = 'pdo_'. strtolower( array_pop($explode) );

        if (!extension_loaded($extension))
            throw new \RuntimeException('Need '. $extension .' extension!');

        $this->cfg = self::parseConfig($config);
    }

    public function __call($fn, $args) {
        $this->connect();

        if (method_exists($this->dbh, $fn))
            return call_user_func_array(array($this->dbh, $fn), $args);

        throw new \BadMethodCallException('Bad method: '. $fn);
    }

    /**
     * 魔法方法，函数式调用
     *
     * $db = \Lysine\Db::factory($dsn, $user, $pass);
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

        list($dsn, $user, $pass, $options) = $this->cfg;

        $dbh = new \PDO($dsn, $user, $pass, $options);

        // 出错时抛出异常
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // 这里允许通过构造时传递的options定义自己的result class
        list($result_class) = $dbh->getAttribute(\PDO::ATTR_STATEMENT_CLASS);
        if ($result_class == 'PDOStatement') {
            $dbh->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\Lysine\Db\Result\Pdo'));
        }

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

    public function __sleep() {
        $this->disconnect();
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
     * @return void
     */
    public function begin() {
        $this->connect();
        $this->dbh->beginTransaction();
    }

    /**
     * 回滚事务
     *
     * @access public
     * @return void
     */
    public function rollback() {
        $this->dbh->rollBack();
    }

    /**
     * 提交事务
     *
     * @access public
     * @return void
     */
    public function commit() {
        $this->dbh->commit();
    }

    /**
     * 执行sql并返回结果或结果对象
     *
     * @param string $sql
     * @param mixed $bind
     * @access public
     * @return mixed
     */
    public function execute($sql, $bind = null) {
        $this->connect();
        if (!is_array($bind)) $bind = array_slice(func_get_args(), 1);

        try {
            $sth = $this->dbh->prepare($sql);
            $sth->execute($bind);
        } catch (\PDOException $ex) {
            throw new Db\Exception(
                $ex->getMesssage(),
                $ex->getCode(),
                $ex,
                $ex->errorInfo[0]
            );
        }

        if (strtolower(substr($sql, 0, 6)) == 'select') {
            $sth->setFetchMode(\PDO::FETCH_ASSOC);
            return $sth;
        }

        return $sth->rowCount();
    }

    /**
     * 生成Lysine\Db\Select实例
     *
     * @param string $table_name
     * @access public
     * @return Lysine\Db\Select
     */
    public function select($table_name) {
        $select = new Select($this);
        return $select->from($table_name);
    }

    /**
     * 插入一条记录
     *
     * @param string $table_name
     * @param array $row
     * @access public
     * @return integer
     */
    public function insert($table_name, array $row) {
        $this->connect();

        $cols = array_keys($row);
        $vals = array_values($row);

        $place = array();
        foreach ($cols as $col) {
            $place[] = ':'. $col;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->qtab($table_name),
            implode(',', $this->qcol($cols)),
            implode(',', $place)
        );

        return $this->execute($sql, $vals);
    }

    /**
     * 更新记录
     *
     * $adapter->update('users', array('passwd' => 'abc'), 'id = ?', 1);
     * $adapter->update('users', array('passwd' => 'abc'), array('id = ?', 1));
     * $adapter->update('table', array('passwd' => 'abc'), 'id = :id', array(':id' => 1));
     * $adapter->update('table', array('passwd' => 'abc'), 'id = :id', 1);
     *
     * @param string $table_name
     * @param array $row
     * @param mixed $where
     * @param mixed $bind
     * @access public
     * @return integer
     */
    public function update($table_name, array $row, $where = null, $bind = null) {
        $this->connect();

        // 先解析where
        $where_bind = array();
        if (is_null($where)) {
            $where = null;
            $where_bind = array();
        } elseif (is_array($where)) {
            list($where, $where_bind) = call_user_func_array(array($this, 'parsePlaceHolder'), $where);
        } else {
            $args = func_get_args();
            list($where, $where_bind) = call_user_func_array(array($this, 'parsePlaceHolder'), array_slice($args, 2));
        }

        //检查place holder类型
        $holder = null;
        if ($where_bind AND is_int(key($where_bind))) $holder = '?';

        $set = $bind = array();
        while (list($col, $val) = each($row)) {
            $holder_here = $holder ? $holder : ':'. $col;
            $set[] = $this->qcol($col) .' = '. $holder_here;

            if ($holder_here == '?') {
                $bind[] = $val;
            } else {
                $bind[$holder_here] = $val;
            }
        }
        $bind = array_merge($bind, $where_bind);

        $sql = sprintf('UPDATE %s SET %s', $this->qtab($table_name), implode(',', $set));
        if ($where) $sql .= ' WHERE '. $where;

        return $this->execute($sql, $bind);
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
     * @param string $table_name
     * @param mixed $where
     * @param mixed $bind
     * @access public
     * @return integer
     */
    public function delete($table_name, $where = null, $bind = null) {
        $this->connect();

        $bind = array();

        $sql = 'DELETE FROM '. $this->qtab($table_name);
        if (is_null($where)) {
            $where = $bind = null;
        } elseif (is_array($where)) {
            list($where, $bind) = call_user_func_array(array($this, 'parsePlaceHolder'), $where);
        } else {
            $args = func_get_args();
            list($where, $bind) = call_user_func_array(array($this, 'parsePlaceHolder'), array_slice($args, 1));
        }

        if ($where) $sql .= ' WHERE '. $where;

        return $this->execute($sql, $bind);
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

        if (is_numeric($val)) return $val;
        if (is_null($val)) return 'NULL';

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
        if (is_null($bind)) return array($sql, array());

        $bind = is_array($bind) ? $bind : array_slice(func_get_args(), 1);

        if (!preg_match_all('/:[a-z0-9_\-]+/i', $sql, $match))
            return array($sql, array_values($bind));
        $place = $match[0];

        if (count($place) != count($bind))
            throw new \InvalidArgumentException('Missing sql statement parameter');

        return array($sql, array_combine($place, $bind));
    }

    /**
     * 解析pdo adapter配置信息
     *
     * @param array $cfg
     * @static
     * @access public
     * @return array
     */
    static public function parseConfig(array $cfg) {
        if (!isset($cfg['dsn']))
            throw new \InvalidArgumentException('Invalid database config');

        $dsn = $cfg['dsn'];

        $user = isset($cfg['user']) ? $cfg['user'] : null;
        $pass = isset($cfg['pass']) ? $cfg['pass'] : null;
        $options = (isset($cfg['options']) AND is_array($cfg['options']))
                 ? $cfg['options']
                 : array();

        return array($dsn, $user, $pass, $options);
    }
}