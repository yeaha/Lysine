<?php
namespace Lysine {
    class Db {
        const TYPE_INTEGER = 1;
        const TYPE_FLOAT = 2;
        const TYPE_BOOL = 3;
        const TYPE_STRING = 4;
        const TYPE_BINARY = 5;

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

        static public function factory($dsn, $user, $pass, array $options = array()) {
            if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
                throw new \InvalidArgumentException('Invalid dsn');

            $adapter = $match[1];

            $class = __NAMESPACE__ .'\Db\Adapter\\'. ucfirst($adapter);
            return new $class($dsn, $user, $pass, $options);
        }
    }
}

namespace Lysine\Db {
    interface IAdapter {
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
