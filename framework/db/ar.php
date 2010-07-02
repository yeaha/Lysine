<?php
namespace Lysine\Db;

abstract class ActiveRecord {
    static protected $table_name;

    static protected $table;

    protected $adapter;

    protected $row;

    protected $from_db;

    protected $ref = array();

    public function __construct(array $row = array(), $from_db = false) {
        $this->row = $row;
        $this->from_db = $from_db;
    }

    public function __get($key) {
    }

    public function __set($key, $val) {
    }

    public function setAdapter(Adapter $adapter) {
        $this->adapter = $adapter;
        return $this;
    }

    public function getAdapter() {
        return $this->adapter;
    }

    public function save() {
    }

    protected function insert() {
    }

    protected function update() {
    }

    public function destroy() {
    }

    static public function find($id) {
        // TODO: get primary key
        return static::select()->where("{$pk} = ?", $id)->get(1);
    }

    static public function select() {
        $class = get_called_class();
        $processor = function($row) use ($class) {
            return $row ? new $class($row, true) : new $class();
        };

        $select = new Select($this->getAdapter());
        $select->from(static::$table_name)->preProcessor($processor);

        if ($args = func_get_args()) call_user_func_array(array($select, 'where'), $args);

        return $select;
    }
}
