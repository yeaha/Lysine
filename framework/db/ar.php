<?php
namespace Lysine\Db;

abstract class ActiveRecord {
    static protected $db_config_path;

    static protected $table_name;

    static protected $primary_key;

    protected $table;

    protected $adapter;

    protected $row;

    protected $ref = array();

    public function __construct(array $row = array()) {
        $this->row = $row;
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
        if (!$this->adapter) $this->adapter = Lysine\Db::connect();
        return $this->adapter;
    }

    public function getTable() {
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
        return static::select()->where(static::primary_key .' = ?', $id)->get(1);
    }

    static public function select() {
        $class = get_called_class();
        $processor = function($row) use ($class) {
            return $row ? new $class($row, true) : new $class();
        };

        $select = new Select($this->getAdapter());
        $select->from(static::$table_name)->setProcessor($processor);

        if ($args = func_get_args()) call_user_func_array(array($select, 'where'), $args);

        return $select;
    }
}

class ActiveRecord_Meta {
    static public $instance;

    protected $table = array();

    static public function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function getTable($class) {
    }
}
