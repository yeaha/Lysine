<?php
namespace Lysine\Db;

class Row {
    protected $row;
    protected $dirty_row = array();
    protected $table;

    public function __construct(array $row = array(), Table $table = null) {
        $this->row = $row;
        if ($table) $this->table = $table;
    }

    public function __set() {
    }

    public function __get() {
    }

    public function set($col, $val = null) {
        if (is_array($col)) {
            $this->dirty_row = array_merge($this->dirty_row, $col);
        } else {
            $this->dirty_row[$col] = $val;
        }
        return $this;
    }

    public function get($col) {
        if (isset($this->dirty_row[$col])) return $this->dirty_row[$col];
        if (isset($this->row[$col])) return $this->row[$col];
        throw \InvalidArgumentException('Invalid column '. $col);
    }

    public function setTable(Table $table) {
        $this->table = $table;
        return $this;
    }

    public function getTable() {
        return $this->table;
    }

    public function refresh() {
    }

    public function delete() {
    }

    public function save() {
    }

    protected function insert() {
    }

    protected function update() {
    }

    public function toArray() {
        return array_merge($this->row, $this->dirty_row);
    }
}
