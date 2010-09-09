<?php
namespace Lysine\Storage\DB\Adapter;

use Lysine\Storage\DB;
use Lysine\Storage\DB\Adapter;
use Lysine\Storage\DB\Expr;

class Sqlite extends Adapter {
    /**
     * savepoint序号
     *
     * @var integer
     * @access private
     */
    private $savepoint = 0;

    /**
     * 是否处于事务中
     *
     * @var boolean
     * @access private
     */
    private $in_transaction = false;

    /**
     * 开始事务
     *
     * @access public
     * @return boolean
     */
    public function begin() {
        if ($this->in_transaction) {
            $savepoint = 'savepoint_'. ++$this->savepoint;
            $this->exec('SAVEPOINT '. $savepoint);
        } else {
            $this->exec('BEGIN');
            $this->in_transaction = true;
        }
        return true;
    }

    /**
     * 回滚事务
     *
     * @access public
     * @return boolean
     */
    public function rollback() {
        if (!$this->in_transaction) return false;

        if ($this->savepoint) {
            $savepoint = 'savepoint_'. $this->savepoint--;
            $this->exec('ROLLBACK TO SAVEPOINT '. $savepoint);
        } else {
            $this->exec('ROLLBACK');
            $this->in_transaction = false;
        }
        return true;
    }

    /**
     * 提交事务
     *
     * @access public
     * @return boolean
     */
    public function commit() {
        if (!$this->in_transaction) return false;

        if ($this->savepoint) {
            $savepoint = 'savepoint_'. $this->savepoint--;
            $this->exec('RELEASE SAVEPOINT '. $savepoint);
        } else {
            $this->exec('COMMIT');
            $this->in_transaction = false;
        }
        return true;
    }

    /**
     * 获得表名的完全限定名
     *
     * @param string $table_name
     * @access public
     * @return string
     */
    public function qtab($table_name) {
        return $this->qcol($table_name);
    }

    /**
     * 获得字段名的完全限定名
     *
     * @param string $col_name
     * @access public
     * @return string
     */
    public function qcol($col_name) {
        if ($col_name instanceof Expr) return $col_name->__toString();
        return '"'. trim($col_name, '"') .'"';
    }

    /**
     * 获得自增长字段的最后一次插入的主键值
     *
     * @param string $table_name
     * @param string $column
     * @access public
     * @return integer
     */
    public function lastId($table_name = null, $column = null) {
        return $this->execute('SELECT last_insert_rowid()')->getCol();
    }
}
