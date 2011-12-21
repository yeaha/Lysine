<?php
namespace Lysine\Storage\DB\Adapter;

use Lysine\Error,
    Lysine\Storage\DB,
    Lysine\Storage\DB\Adapter,
    Lysine\Storage\DB\Expr;

if (!extension_loaded('pdo_mysql'))
    throw Error::require_extension('pdo_mysql');

class Mysql extends Adapter {
    /**
     * 开始事务
     *
     * @access public
     * @return void
     */
    public function begin() {
        $this->exec('SET AUTOCOMMIT = 0');
        $this->exec('START TRANSACTION');
        $this->in_transaction = true;
    }

    /**
     * 回滚事务
     *
     * @access public
     * @return void
     */
    public function rollback() {
        $this->exec('ROLLBACK');
        $this->exec('SET AUTOCOMMIT = 1');
        $this->in_transaction = false;
    }

    /**
     * 提交事务
     *
     * @access public
     * @return void
     */
    public function commit() {
        $this->exec('COMMIT');
        $this->exec('SET AUTOCOMMIT = 1');
        $this->in_transaction = false;
    }

    /**
     * 最后一次生成的自增长序列的值
     *
     * @param string $table_name
     * @param string $column
     * @access public
     * @return integer
     */
    public function lastId($table_name = null, $column = null) {
        return $this->execute('SELECT last_insert_id()')->getCol();
    }

    /**
     * 把table name转换成为符合格式的完全限定名
     * table name和column name的处理方式完全一致
     * 分开为两个不同的方法增加可读性而已
     *
     * @param mixed $table_name
     * @access public
     * @return string
     */
    public function qtab($table_name) {
        return $this->qcol($table_name);
    }

    /**
     * 把字段名转换为符合格式的完全限定名
     *
     * @param mixed $col_name
     * @access public
     * @return string
     */
    public function qcol($col_name) {
        if ($col_name instanceof Expr) return $col_name;

        $col_name = explode('.', $col_name);
        foreach ($col_name as $key => $col)
            $col_name[$key] = '`'. trim($col, '`') .'`';
        return implode('.', $col_name);
    }
}
