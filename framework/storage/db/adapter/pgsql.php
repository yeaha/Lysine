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
     * 获得字段定义
     * 返回二维数组
     * 抄袭自zend framework
     *
     * [code]
     * array(
     *     array(
     *         'name' => 'id',              // column name
     *         'ctype' => DB::TYPE_INTEGER,   // common type
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
        static $typeMap = array(
            'image' => DB::TYPE_BINARY,
            'blob' => DB::TYPE_BINARY,
            'bit' => DB::TYPE_BINARY,
            'varbit' => DB::TYPE_BINARY,
            'bytea' => DB::TYPE_BINARY,
            'bool' => DB::TYPE_BOOL,
            'boolean' => DB::TYPE_BOOL,
            'smallint' => DB::TYPE_INTEGER,
            'integer' => DB::TYPE_INTEGER,
            'bigint' => DB::TYPE_INTEGER,
            'int2' => DB::TYPE_INTEGER,
            'int4' => DB::TYPE_INTEGER,
            'int8' => DB::TYPE_INTEGER,
            'oid' => DB::TYPE_INTEGER,
            'serial' => DB::TYPE_INTEGER,
            'bigserial' => DB::TYPE_INTEGER,
        );

        list($schema, $table) = $this->_parseTableName($table);

        $bind = array($table);

        $sql = <<< EOF
SELECT a.attnum, n.nspname, c.relname, a.attname as colname, t.typname as type, a.atttypmod,
       FORMAT_TYPE(a.atttypid, a.atttypmod) AS complete_type,
       d.adsrc AS default_value,
       a.attnotnull AS notnull,
       a.attlen AS length,
       co.contype,
       ARRAY_TO_STRING(co.conkey, ',') AS conkey
FROM pg_attribute AS a
     JOIN pg_class AS c ON a.attrelid = c.oid
     JOIN pg_namespace AS n ON c.relnamespace = n.oid
     JOIN pg_type AS t ON a.atttypid = t.oid
     LEFT OUTER JOIN pg_constraint AS co ON (co.conrelid = c.oid
         AND a.attnum = ANY(co.conkey) AND co.contype = 'p')
     LEFT OUTER JOIN pg_attrdef AS d ON d.adrelid = c.oid AND d.adnum = a.attnum
WHERE a.attnum > 0 AND c.relname = ?
EOF;
        if ($schema) {
            $sql .= ' AND n.nspname = ?';
            $bind[] = $schema;
        }
        $sql .= ' ORDER BY a.attnum';

        $sth = $this->execute($sql, $bind);

        $cols = array();
        while ($row = $sth->getRow()) {
            list($primary_key, $auto_increment) = array(false, false);
            if ($row['contype'] == 'p') {
                $primary_key = true;
                $auto_increment = (bool) preg_match('/^nextval/i', $row['default_value']);
            }

            $default_value = $row['default_value'];
            if ($row['type'] == 'varchar' OR $row['type'] == 'bpchar') {
                if (preg_match('/character(?: varying)?(?:\((\d+)\))?/', $row['complete_type'], $match))
                    $row['length'] = isset($match[1]) ? $match[1] : null;

                if (preg_match("/^'(.*?)'::(?:character varying|bpchar)$/", $default_value, $match))
                    $default_value = $match[1];
            }

            $ctype = isset($typeMap[$row['type']]) ? $typeMap[$row['type']] : DB::TYPE_STRING;
            $col = array(
                'schema' => $schema,
                'table' => $table,
                'name' => $row['colname'],
                'ctype' => $ctype,
                'ntype' => $row['complete_type'],
                'length' => $row['length'],
                'allow_null' => !$row['notnull'],
                'default_value' => $default_value,
                'primary_key' => $primary_key,
                'auto_increment' => $auto_increment,
            );
            $cols[$row['colname']] = $col;
        }
        return $cols;
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
        $where = array(
            'tablename NOT SIMILAR TO \'(pg_|sql_|information_)%\''
        );
        $bind = array();
        if ($schema) {
            $where[] = 'schemaname = ?';
            $bind[] = $schema;
        }

        if ($pattern) {
            $where[] = 'tablename ILIKE ?';
            $bind[] = $pattern;
        }

        $sql = sprintf('SELECT schemaname, tablename FROM pg_tables WHERE %s', implode(' AND ', $where));
        $tables = array();
        foreach ($this->execute($sql, $bind)->getAll() as $row)
            $tables[] = $this->qtab("{$row['schemaname']}.{$row['tablename']}");

        return $tables;
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
        $where = array(
            'viewname NOT SIMILAR TO \'(pg_|sql_|information_)%\' AND schemaname NOT SIMILAR TO \'(pg_|sql_|information_)%\''
        );
        $bind = array();
        if ($schema) {
            $where[] = 'schemaname = ?';
            $bind[] = $schema;
        }

        if ($pattern) {
            $where[] = 'viewname ILIKE ?';
            $bind[] = $pattern;
        }

        $sql = sprintf('SELECT viewname FROM pg_views WHERE %s', implode(' AND ', $where));

        return $this->execute($sql, $bind)->getCols();
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
        if (!is_array($contype)) $contype = array_slice(func_get_args(), 1);

        $constraints = array();

        $current_table_oid = $this->_tableOid($table_name);
        $current_table_attribute = $this->_tableAttribute($current_table_oid, 'attnum');

        // 从pg_constraint查询出表的所有约束定义
        $where = array('conrelid = ?');
        $bind = array($current_table_oid);
        if ($contype)
            $where[] = sprintf('contype IN (\'%s\')', implode('\',\'', $contype));

        $sql = sprintf('SELECT * FROM pg_constraint WHERE %s', implode(' AND ', $where));
        foreach ($this->execute($sql, $bind)->getAll() as $row) {
            // 约束所在的字段
            $concolumns = array();
            foreach (self::decodeArray($row['conkey']) as $attnum)
                $concolumns[] = $current_table_attribute[$attnum]['attname'];

            // 开始处理不同类型的约束
            if ('p' == $row['contype']) {  // 主键约束
                $constraints['p'] = $concolumns;
            } else if ('f' == $row['contype']) {  // 外键约束
                $fk = array(
                    'columns' => $concolumns
                );
                $referer_table_oid = $row['confrelid'];
                // 被引用的表的字段信息
                $referer_table_attribute = $this->_tableAttribute($referer_table_oid, 'attnum');
                foreach (self::decodeArray($row['confkey']) as $referer_attnum) {
                    $fk['referer_columns'][] = $referer_table_attribute[$referer_attnum]['attname'];
                }
                $referer_table_relname = $this->_tableRelname($referer_table_oid);
                $fk['referer_table'] = $this->qtab(
                    "{$referer_table_relname['schema']}.{$referer_table_relname['table']}"
                );

                $constraints['f'][] = $fk;
            } else if ('u' == $row['contype']) {  // 唯一约束
                $constraints['u'][] = $concolumns;
            }
        }
        return $constraints;
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
        list($schema_name, $table_name) = $this->_parseTableName($table_name);
        $indexes = array();

        $current_table_oid = $this->_tableOid($table_name);
        $current_table_attribute = $this->_tableAttribute($current_table_oid, 'attnum');

        // 从pg_index查询出表的所有索引定义
        $sql = sprintf('SELECT * FROM pg_index WHERE indrelid = %d', $current_table_oid);
        foreach ($this->execute($sql)->getAll() as $row) {
            $idx = array();

            $indcolumns = array();
            foreach (explode(' ', $row['indkey']) as $attnum) {
                $indcolumns[] = $current_table_attribute[$attnum]['attname'];
            }
            $idx['columns'] = $indcolumns;

            $idx['is_unique'] = ('t' == $row['indisunique']);
            $idx['is_primary'] = ('t' == $row['indisprimary']);
            $indexes[] = $idx;
        }
        return $indexes;
    }

    /**
     * 从系统表中查询schema的oid
     *
     * @param string $schema_name
     * @access protected
     * @return integer
     */
    protected function _schemaOid($schema_name) {
        static $oid = array();

        if (!array_key_exists($schema_name, $oid)) {
            $sql = sprintf('SELECT oid FROM pg_namespace WHERE nspname = %s', $this->qstr($schema_name));
            $oid[$schema_name] = $this->execute($sql)->getCol();
        }

        return $oid[$schema_name];
    }

    /**
     * 从系统表中查询表的oid
     *
     * @param string $table_name
     * @param string $schema_name
     * @access protected
     * @return integer
     */
    protected function _tableOid($table) {
        static $oid = array();
        list($schmea, $table) = $this->_parseTableName($table);

        $fullname = "{$schema}.{$table}";

        if (!array_key_exists($fullname, $oid)) {
            $where = array('relname = ?');
            $bind = array($table);
            if ($schema) {
                $where[] = sprintf('relnamespace = %d', $this->_schemaOid($schema));
            }
            $sql = sprintf('SELECT oid FROM pg_class WHERE %s', implode(' AND ', $where));
            $oid[$fullname] = $this->execute($sql, $bind)->getCol();
        }

        return $oid[$fullname];
    }

    /**
     * 从系统表中查询指定表的字段信息
     *
     * @param integer $table_oid
     * @param string $result_key
     * @access protected
     * @return array
     */
    protected function _tableAttribute($table_oid, $result_key = null) {
        $sql = sprintf('SELECT * FROM pg_attribute WHERE attrelid = %d AND attnum > 0', $table_oid);
        return $this->execute($sql)->getAll($result_key);
    }

    /**
     * 从表的oid获得表名和schema名
     *
     * @param integer $table_oid
     * @access protected
     * @return array
     */
    protected function _tableRelname($table_oid) {
        static $relname = array();

        if (!array_key_exists($table_oid, $relname)) {
            $sql = sprintf('SELECT relname, relnamespace FROM pg_class WHERE oid = %d', $table_oid);
            $row = $this->execute($sql)->getRow();
            $relname[$table_oid]['table'] = $row['relname'];
            $relname[$table_oid]['schema'] = $this->_schemaNspname($row['relnamespace']);
        }

        return $relname[$table_oid];
    }

    /**
     * 从schema oid获得schema名字
     *
     * @param integer $schema_oid
     * @access protected
     * @return string
     */
    protected function _schemaNspname($schema_oid) {
        static $nspname = array();

        if (!array_key_exists($schema_oid, $nspname)) {
            $sql = sprintf('SELECT nspname FROM pg_namespace WHERE oid = %d', $schema_oid);
            $nspname[$schema_oid] = $this->execute($sql)->getCol();
        }

        return $nspname[$schema_oid];
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
    protected function _parseTableName($table_name) {
        $table_name = str_replace('"', '', $table_name);

        $parts = explode('.', $table_name);
        if (count($parts) < 2) array_unshift($parts, null);

        return $parts;
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
        foreach ($array as &$val) $val = trim($val, '"');
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
            list($k, $v) = explode('=>', $pair);
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
                $v = str_replace('"', '\"', $v);
                $result[] = sprintf('"%s"=>"%s"', $k, $v);
            }
            return implode(',', $result);
        } else {
            $result = 'hstore(ARRAY[%s], ARRAY[%s])';
            $cols = $vals = array();
            foreach ($array as $k => $v) {
                $cols[] = $k;
                $vals[] = str_replace("'", "''", $v);
            }

            return new Expr(sprintf(
                $result,
                "'". implode("','", $cols) ."'",
                "'". implode("','", $vals) ."'"
            ));
        }
    }
}
