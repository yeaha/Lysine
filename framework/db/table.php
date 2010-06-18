<?php
class Ly_Db_Table {
    protected $adapter;
    protected $table;

    protected $row_class = 'Ly_Db_Row';

    public function __construct(Ly_Db_Adapter_Abstract $adapter, $table) {
        $this->table = $table;
        $this->adapter = $adapter;
    }

    public function adapter() {
        return $this->adapter;
    }

    public function select() {
        $select = new Ly_Db_Select($this->adapter);
        return $select->from($this->table)->returnAs($this->row_class, array($this->adapter, $this), array(true));
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

    public function __toString() {
        return $this->table;
    }
}
