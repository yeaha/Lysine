<?php
namespace Lysine\Db;

class Row {
    protected $row;
    protected $dirty_row = array();
    protected $table;

    public function __construct(array $row, Table $table) {
        $this->row = $row;
        $this->table = $table;
    }

    public function __set($col, $val) {
        $this->set($col, $val);
    }

    public function __get($col) {
        return $this->get($col);
    }

    public function set($col, $val = null) {
        if (is_array($col)) {
            foreach ($col as $k => $v) $this->set($k, $v);
        } else {
            if (array_key_exists($col, $this->row))
                throw new \InvalidArgumentException('Invalid column name['. $col .']');
            $this->dirty_row[$col] = $val;
        }
        return $this;
    }

    public function get($col) {
        if (array_key_exists($col, $this->dirty_row)) return $this->dirty_row[$col];
        if (array_key_exists($col, $this->row)) return $this->row[$col];

        throw new \InvalidArgumentException('Invalid column name['. $col .']');
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
