<?php
namespace Lysine\ORM\ActiveRecord;

use Lysine\IStorage;
use Lysine\Storage\DB\IAdapter;
use Lysine\ORM\ActiveRecord;
use Lysine\ORM\Registry;

abstract class DBActiveRecord extends ActiveRecord {
    static public function find($key, IStorage $storage = null) {
        $class = get_called_class();
        if ($ar = Registry::get($class, $key)) return $ar;

        $primary_key = static::$primary_key;
        return static::select($storage)->where("{$primary_key} = ?", $key)->get(1);
    }

    static public function select(IAdapter $adapter = null) {
        if (!$adapter) $adapter = static::getStorage();

        $class = get_called_class();
        $processor = function($record) use ($class) {
            return $record ? new $class($record, false) : false;
        };

        return $adapter->select(static::$collection)
                       ->setProcessor($processor)
                       ->setKeyColumn(static::$primary_key);
    }

    protected function insert(IStorage $storage = null) {
        $record = $this->toArray();
        $table = static::getCollection();
        $primary_key = static::getPrimaryKey();
        if (!$storage) $storage = static::getStorage();

        if (!$storage->insert($table, $record)) return false;

        $new_primary_key = isset($record[$primary_key])
                         ? $record[$primary_key]
                         : $storage->lastId($table, $primary_key);
        return $new_primary_key;
    }

    protected function update(IStorage $storage = null) {
        $record = $this->toArray(/*only dirty*/true);
        $table = static::getCollection();
        if (!$storage) $storage = static::getStorage();
        $primary_key = $storage->qcol(static::getPrimaryKey());

        return $storage->update($table, $record, "{$primary_key} = ?", $this->id());
    }

    protected function delete(IStorage $storage = null) {
        $table = static::getCollection();
        if (!$storage) $storage = static::getStorage();
        $primary_key = $storage->qcol(static::getPrimaryKey());

        return $storage->delete($table, "{$primary_key} = ?", $this->id());
    }
}
