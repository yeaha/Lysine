<?php
namespace Lysine\Storage\DB\Adapter;

use Lysine\Storage\DB;
use Lysine\Storage\DB\Adapter;
use Lysine\Storage\DB\Expr;
use Lysine\Storage\DB\Select\Pgsql as Select;

class Pgsql extends Adapter {
    /**
     * savepoint序号
     *
     * @var integer
     * @access private
     */
    private $savepoint = 0;

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
     * 生成Lysine\Storage\DB\Select\Pgsql实例
     *
     * @param string $table_name
     * @access public
     * @return Lysine\Storage\DB\Select\Pgsql
     */
    public function select($table_name) {
        $select = new Select($this);
        return $select->from($table_name);
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
     * 根据表名和字段名生成序列名
     *
     * @param string $table_name
     * @param string $column
     * @access protected
     * @return string
     */
    protected function seqName($table_name, $column) {
        list($schema, $seq_name) = $this->parseTableName($table_name);
        $seq_name .= '_'. $column .'_seq';
        return $schema ? sprintf('"%s"."%s"', $schema, $seq_name) : $this->qcol($seq_name);
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
        if ($table_name && $column) {
            $sql = sprintf("SELECT CURRVAL('%s')", $this->seqName($table_name, $column));
        } else {
            $sql = 'SELECT LASTVAL()';
        }

        try {
            return $this->execute($sql)->getCol();
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * 下一个自增长序列的值
     *
     * @param string $table_name
     * @param string $column
     * @access public
     * @return integer
     */
    public function nextId($table_name, $column) {
        $seq_name = $this->seqName($table_name, $column);
        try {
            return $this->execute("SELECT NEXTVAL('{$seq_name}')")->getCol();
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * 把表名称的数据库名称、schame名称和表名称解析为数组返回
     * 解析出来的名称都已经用trim()处理过
     *
     * schema参数是为了配合以前写的nextId()、qtable()、metaColumns()函数
     * 这几个函数允许指定schema参数
     * 如果在$table_name中没有解析出schema名称，则使用schema参数的值
     * 否则使用解析出的schema名称
     * 简单来说就是$table_name的信息优先
     *
     * @param string $table_name
     * @access protected
     * @return array
     */
    protected function parseTableName($table_name) {
        $parts = explode('.', $table_name);
        foreach ($parts as $k => $v) $parts[$k] = trim($v, '"');

        $table = array_pop($parts);
        $schema = array_pop($parts);
        return array($schema, $table);
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
        if (is_array($col_name)) {
            foreach ($col_name as &$c) $c = $this->qcol($c);
            return $col_name;
        }

        if ($col_name instanceof Expr) return $col_name;
        if (substr($col_name, 0, 1) == '"') return $col_name;
        if (strpos($col_name, '.') === false) return '"'. $col_name .'"';

        $parts = explode('.', $col_name);
        foreach ($parts as &$p) $p = '"'. trim($p, '"') .'"';
        return implode('.', $parts);
    }

    /**
     * 把postgresql数组转换为php数组
     * 仅支持一维数组
     *
     * @param string $pgArray
     * @access public
     * @return array
     */
    public static function decodeArray($array) {
        $array = explode(',', trim($array, '{}'));
        return $array;
    }

    /**
     * 把php数组转换为postgresql数组字符串
     * 仅支持一维数组
     *
     * @param array $phpArray
     * @access public
     * @return string
     */
    public static function encodeArray(array $array) {
        return $array ? sprintf('{"%s"}', implode('","', $array)) : null;
    }

    /**
     * 把postgresql hstore返回结果转换为php数组
     *
     * @param string $hstore
     * @static
     * @access public
     * @return array
     */
    public static function decodeHstore($hstore) {
        $result = array();
        if (!$hstore) return $result;

        foreach (preg_split('/"\s*,\s*"/', $hstore) as $pair) {
            $pair = explode('=>', $pair);
            if (count($pair) !== 2) continue;

            list($k, $v) = $pair;
            $k = trim($k, '\'" ');
            $v = trim($v, '\'" ');
            $result[$k] = $v;
        }
        return $result;
    }

    /**
     * 把php数组转换为postgresql hstore字符串
     *
     * @param array $array
     * @param boolean $new_style 使用postgresql 9.0之后的新格式
     * @static
     * @access public
     * @return string
     */
    public static function encodeHstore(array $array, $new_style = false) {
        if (!$array) return null;

        if (!$new_style) {
            $result = array();
            foreach ($array as $k => $v) {
                $v = str_replace('\\', '\\\\\\\\', $v);
                $v = str_replace('"', '\\\\"', $v);
                $v = str_replace("'", "\\'", $v);
                $result[] = sprintf('"%s"=>"%s"', $k, $v);
            }
            return new Expr('E\''. implode(',', $result) .'\'::hstore');
        } else {
            $result = 'hstore(ARRAY[%s], ARRAY[%s])';
            $cols = $vals = array();
            foreach ($array as $k => $v) {
                $v = str_replace('\\', '\\\\', $v);
                $v = str_replace("'", "\\'", $v);
                $cols[] = $k;
                $vals[] = $v;
            }

            return new Expr(sprintf(
                $result,
                "'". implode("','", $cols) ."'",
                "E'". implode("',E'", $vals) ."'"
            ));
        }
    }
}
