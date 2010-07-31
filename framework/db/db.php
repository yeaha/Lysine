<?php
namespace Lysine {
    class Db {
        const TYPE_INTEGER = 1;
        const TYPE_FLOAT = 2;
        const TYPE_BOOL = 3;
        const TYPE_STRING = 4;
        const TYPE_BINARY = 5;

        static public function factory($class, array $config) {
            if (substr($class, 0, 1) != '\\')
                $class = '\Lysine\Db\Adapter\\'. $class;
            return new $class($config);
        }
    }
}

namespace Lysine\Db {
    interface IAdapter {
        public function __construct(array $config);
        public function getHandle();
        public function begin();
        public function rollback();
        public function commit();
        public function execute($sql, $bind = null);
        public function select($table_name);
        public function insert($table_name, array $row);
        public function update($table_name, array $row, $where = null, $bind = null);
        public function delete($table_name, $where = null, $bind = null);
        public function qtab($table_name);
        public function qcol($column_name);
        public function qstr($val);
    }

    interface IStatement {
        public function getRow();
        public function getCol($col_number = 0);
        public function getCols($col_number = 0);
        public function getAll($col = null);
    }
}
