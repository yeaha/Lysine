<?php
class Ly_Db_Adapter_Pgsql extends Ly_Db_Adapter_Abstract {
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
        foreach ($parts as &$p) $p = trim($p, '"');

        $result = array();
        $result['table'] = array_pop($parts);

        $schema = array_pop($parts);
        if ($schema) $result['schema'] = $schema;

        return $result;
    }

    /**
     * 把table name转换成为符合格式的完全限定名
     *
     * @param mixed $table_name
     * @param string $alias
     * @access public
     * @return string
     */
    public function qtab($table_name) {
        if (substr($table_name, 0, 1) == '"') return $table_name;

        $parts = explode('.', $table_name);
        foreach ($parts as &$p) $p = '"'. trim($p, '"') .'"';

        return implode('.', $parts);
    }

    public function qcol($column_name) {
        if (is_array($column_name)) {
            foreach ($column_name as &$c) $c = $this->qcol($c);
            return $column_name;
        }
        if ($column_name instanceof Ly_Db_Expr) return $column_name->__toString();
        if (substr($column_name, 0, 1) == '"') return $column_name;

        return '"'. $column_name .'"';
    }
}
