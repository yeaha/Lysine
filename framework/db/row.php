<?php
class Ly_Db_Row extends Ly_Events {
    protected $adapter;
    protected $table;
    protected $primary_key;
    protected $from_storage;
    protected $row = array();
    protected $dirty_cols = array();

    public function __construct(Ly_Db_Adapter_Abstract $adapter, $table, array $row = null, $from_storage = false) {
        $this->adapter = $adapter;

        if ($table instanceof Ly_Db_Table) {
            $this->table = $table;
        } else {
            $this->table = new Ly_Db_Table($adapter, $table);
        }

        if ($row) $this->row = $row;

        $this->from_storage = $from_storage;
    }

    public function __get($key) {
        if (array_key_exists($key, $this->row)) return $this->row[$key];
        return false;
    }

    public function __set($key, $val) {
        if (array_key_exists($key, $this->row))
            $this->change($key, $val);
    }

    public function change($col, $val = null) {
        if (is_array($col)) {
            foreach ($col as $k => $v) $this->row[$k] = $v;
            $this->dirty_cols = array_unique(array_merge($this->dirty_cols, array_keys($col)));
        } else {
            $this->row[$col] = $val;
            if (!in_array($col, $this->dirty_cols)) $this->dirty_cols[] = $col;
        }

        return $this;
    }

    public function isDirty() {
        return count($this->dirty_cols);
    }

    protected function insert() {
    }

    protected function update() {
    }

    public function save() {
        $this->fireEvent('before save', $this);
        $this->fireEvent('after save', $this);
    }

    static public function invoke($adapter, $table, $row, $from_storage) {
        return new self($adapter, $table, $row, $from_storage);
    }
}
