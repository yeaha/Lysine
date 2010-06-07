<?php
/**
 * 数据库连接
 *
 * @abstract
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
abstract class Ly_Db_Adapter_Abstract {
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
    public function __construct($dsn, $user, $pass, array $options) {
        $extension = 'pdo_'.
                     strtolower(
                         array_pop(
                             explode('_', get_class($this))
                         )
                     );

        if (!extension_loaded($extension))
            throw new Ly_Db_Exception('Need '. $extension .' extension!');

        $this->cfg = array($dsn, $user, $pass, $options);
        $this->connect();
    }

    /**
     * 是否已经连接数据库
     *
     * @access protected
     * @return boolean
     */
    protected function isConnected() {
        return $this->dbh instanceof PDO;
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

        try {
            $this->dbh = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $ex) {
            throw new Ly_Db_Exception(
                $ex->getMessage(),
                $ex->getCode()
            );
        }

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
    public function handle() {
        if (!$this->isConnected()) $this->connect();
        return $this->dbh;
    }

    /**
     * 开始事务
     *
     * @access public
     * @return void
     */
    public function begin() {
        if (!$this->isConnected()) $this->connect();
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

    public function exec($sql) {
        if (!$this->isConnected()) $this->connect();
    }

    /**
     * 插入一条记录
     *
     * @param string $table
     * @param array $row
     * @access public
     * @return integer
     */
    public function insert($table, $row) {
        if (!$this->isConnected()) $this->connect();
    }

    /**
     * 更新记录
     *
     * @param string $table
     * @param array $row
     * @param mixed $where
     * @access public
     * @return integer
     */
    public function update($table, $row, $where) {
        if (!$this->isConnected()) $this->connect();
    }

    /**
     * 删除记录
     *
     * @param string $table
     * @param mixed $where
     * @access public
     * @return integer
     */
    public function delete($table, $where) {
        if (!$this->isConnected()) $this->connect();
    }
}
