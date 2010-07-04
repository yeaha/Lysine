<?php
namespace Lysine\Db;

class Table {
    protected $adapter;
    protected $table_name;
    protected $primary_key;
    protected $row_class;
    protected $columns;

    public function __construct($table_name, Adapter $adapter = null) {
        if ($adapter) $this->adapter = $adapter;
        $this->table_name = $table_name;
    }

    public function getAdapter() {
        if (!$this->adapter) $this->adapter = \Lysine\Db::connect();
        return $this->adapter;
    }

    public function setAdapter(Adapter $adapter) {
        $this->adapter = $adapter;
        return $this;
    }

    public function getTableName() {
        return $this->table_name;
    }

    public function getRowClass() {
        return $this->row_class ? $this->row_class : 'Lysine\Db\Row';
    }

    public function getColumn($name) {
        $columns = $this->getColumns();
        return isset($columns[$name]) ? $columns[$name] : false;
    }

    public function getColumns() {
        if (!$this->columns)
            $this->columns = $this->getAdapter()->listColumns($this->table_name);
        return $this->columns;
    }

    public function getPrimaryKey() {
        if ($this->primary_key) return $this->primary_key;

        $columns = $this->getColumns();
        foreach ($columns as $col) {
            if (!$col['primary_key']) continue;

            if ($this->primary_key === null) {
                $this->primary_key = $col['name'];
            } elseif (is_string($this->primary_key)) {
                $this->primary_key = array($this->primary_key, $col['name']);
            } else {
                $this->primary_key[] = $col['name'];
            }
        }
        return $this->primary_key;
    }

    public function select($where = null) {
        $select = new Select($this->getAdapter());
        $select->from($this->table_name);
        if ($where) call_user_func_array(array($select, 'where'), func_get_args());

        $table = $this;
        $row_class = $this->getRowClass();
        $processor = function($row) use ($table, $row_class) {
            return $row ? new $row_class($row, $table) : false;
        };
        $select->setProcessor($processor);

        return $select;
    }

    public function createRow(array $data = null) {
        $row = array_fill_keys(
            array_keys($this->getColumns()), 'null'
        );

        $class = $this->getRowClass();
        $row = new $class($row, $this);
        if ($data) $row->set($data);
        return $row;
    }

    public function insert(array $row) {
        return $this->getAdapter()->insert($this->table_name, $row);
    }

    public function update(array $row, $where = null) {
        $args = func_get_args();
        array_unshift($args, $this->table_name);
        return call_user_func_array(array($this->getAdapter(), 'update'), $args);
    }

    public function delete($where = null) {
        $args = func_get_args();
        array_unshift($args, $this->table_name);
        return call_user_func_array(array($this->getAdapter(), 'delete'), $args);
    }
}
