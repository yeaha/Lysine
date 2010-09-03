<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM\DataMapper\DBMapper;
use Lysine\ORM\DataMapper\MongoMapper;

interface IData {
    static public function getMapper();
}

abstract class Data implements IData {
    final public function fill(array $data) {
    }

    public function save() {
        return static::getMapper()->save($this);
    }

    public function delete() {
        return static::getMapper()->delete($this);
    }

    static public function find($key) {
        return static::getMapper()->find($key);
    }
}

abstract class DBData extends Data {
    static public function getMapper() {
        return DBMapper::factory(get_called_class());
    }

    static public function select() {
        return static::getMapper()->select();
    }
}

abstract class MongoData extends Data {
    static public function getMapper() {
        return MongoMapper::factory(get_called_class());
    }

    static public function select() {
        return static::getMapper()->select();
    }
}
