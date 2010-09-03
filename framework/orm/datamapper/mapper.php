<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM\DataMapper\Data;
use Lysine\ORM\DataMapper\Meta;

abstract class Mapper {
    static private $instance = array();

    protected $class;

    // 在多库的情况下，光有data class不够
    // 可能需要有data实例的主健值之类的路由线索才能够找到对应的storage
    abstract public function getStorage(Data $data = null);

    abstract public function save(Data $data);

    abstract public function delete(Data $data);

    abstract public function find($key);

    private function __construct($class) {
        $this->class = $class;
    }

    public function getMeta() {
        return Meta::factory($this->class);
    }

    static public function factory($class) {
        if (is_object($class)) $class = get_class($class);

        $mapper_class = get_called_class();
        if (!isset(self::$instance[$class])) self::$instance[$class] = new $mapper_class($class);
        return self::$instance[$class];
    }
}

class DBMapper extends Mapper {
    public function getStorage(Data $class = null) {
    }

    public function find($key) {
        $select = $this->select();
        $primary_key = $this->getMeta()->getPrimaryKey();

        if (is_array($key)) {
            return $select->whereIn($primary_key, $key)->get();
        } else {
            return $select->where($primary_key, $key)->get(1);
        }
    }

    public function select(Data $class = null) {
        $primary_key = $this->getMeta()->getPrimaryKey();

        $data_class = $this->class;
        $processor = function($row) use ($data_class) {
            $data = new $data_class;
            $data->fill($row);
            return $data;
        };

        $select = $this->getStorage($class)
                       ->select($this->getMeta()->getCollection())
                       ->setKeyColumn($primary_key)
                       ->setProcessor($processor);
        return $select;
    }

    public function save(Data $data) {
    }

    public function delete(Data $data) {
    }
}

class MongoMapper extends Mapper {
    public function getStorage(Data $class = null) {
    }

    public function find($key) {
    }

    public function select() {
    }

    public function save(Data $data) {
    }

    public function delete(Data $data) {
    }
}

class BDBMapper extends Mapper {
    public function getStorage(Data $class = null) {
    }

    public function find($key) {
    }

    public function save(Data $data) {
    }

    public function delete(Data $data) {
    }
}
