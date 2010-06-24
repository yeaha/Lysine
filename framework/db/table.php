<?php
namespace Lysine\Storage\Db;

class Table {
    protected $adapter;
    protected $table;

    public function __construct(Adapter $adapter, $table) {
        $this->adapter = $adapter;
        $this->table = $table;
    }

    public function adapter() {
        return $this->adapter;
    }

    public function select($where = null) {
        $select = new Select($this->adapter);
        $select->from($this->table);
        if ($where) call_user_func_array(array($select, 'where'), func_get_args());
        return $select;
    }

    public function insert(array $row) {
        return $this->adapter->insert($this->table, $row);
    }

    public function update(array $row, $where = null) {
        $args = func_get_args();
        array_unshift($args, $this->table);
        return call_user_func_array(array($this->adapter, 'update'), $args);
    }

    public function delete($where = null) {
        $args = func_get_args();
        array_unshift($args, $this->table);
        return call_user_func_array(array($this->adapter, 'delete'), $args);
    }
}
