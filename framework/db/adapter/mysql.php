<?php
namespace Lysine\Db\Adapter;

use Lysine\Db;
use Lysine\Db\Adapter;
use Lysine\Db\Expr;

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
        if ($col_name instanceof Expr) return $col_name->__toString();

        $col_name = explode('.', $col_name);
        while (list($key, $col) = each($col_name)) {
            $col_name[$key] = '`'. trim('`', $col) .'`';
        }
        return implode('.', $col_name);
    }

    /**
     * 获得字段定义
     * 返回二维数组
     * 抄袭自zend framework
     *
     * [code]
     * array(
     *     array(
     *         'name' => 'id',              // column name
     *         'ctype' => Db::TYPE_INTEGER,   // common type
     *         'ntype' => 'integer',        // native type
     *         'length' => null,
     *         'allow_null' => false,
     *         'has_default' => true,
     *         'default' => 1,              // default value
     *         'primary_key' => true,       // is primary key?
     *     )
     * )
     * [/code]
     *
     * @param string $table
     * @access public
     * @return array
     */
    public function listColumns($table) {
        return array();
    }

    /**
     * 获得数据库中所有的表，不包括视图
     *
     * @param string $pattern
     * @param string $schema
     * @access public
     * @return array
     */
    public function listTables($pattern = null, $schema = null) {
        return array();
    }

    /**
     * 获得数据库中所有的视图名称
     *
     * @param string $pattern
     * @param string $schema
     * @access public
     * @return array
     */
    public function listViews($pattern = null, $schema = null) {
        return array();
    }

    /**
     * 获得表的约束关系
     * contype指定只查询哪些类型的约束
     * p: 主键约束
     * f: 外键约束
     * u: 唯一约束
     *
     * @param string $table_name
     * @param string $schema_name
     * @param array $contype
     * @access public
     * @return array
     */
    public function listConstraints($table_name, $contype = null) {
        return array();
    }

    /**
     * 获得表的索引信息
     *
     * @param string $table_name
     * @param string $schema_name
     * @access public
     * @return array
     */
    public function listIndexes($table_name) {
        return array();
    }
}
