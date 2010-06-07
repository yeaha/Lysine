<?php
/**
 * 数据库连接
 *
 * @abstract
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
abstract class Ly_Db_Adapter_Abstract {
    protected $cfg;

    protected $dbh;

    public function __construct($dsn, $user, $pass, $options) {
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

    protected function isConnected() {
        return $this->dbh instanceof PDO;
    }

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

    protected function disconnect() {
        $this->dbh = null;
    }

    public function __sleep() {
        $this->disconnect();
    }

    public function handle() {
        if (!$this->isConnected()) $this->connect();
        return $this->dbh;
    }

    public function exec($sql) {
        if (!$this->isConnected()) $this->connect();
    }

    public function insert($table, $row) {
        if (!$this->isConnected()) $this->connect();
    }

    public function update($table, $row, $where) {
        if (!$this->isConnected()) $this->connect();
    }

    public function delete($table, $where) {
        if (!$this->isConnected()) $this->connect();
    }
}
