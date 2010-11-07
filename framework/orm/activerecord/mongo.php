<?php
namespace Lysine\ORM\ActiveRecord;

use Lysine\IStorage;
use Lysine\OrmError;
use Lysine\ORM\ActiveRecord;
use Lysine\ORM\Registry;

abstract class MongoActiveRecord extends ActiveRecord {
    static public function find($key, IStorage $storage = null) {
        $class = get_called_class();
        if ($ar = Registry::get($class, $key)) return $ar;

        if (!$storage) $storage = static::getStorage();
        $record = $storage->findOne(
            static::getCollection(),
            array(static::getPrimaryKey() => $key)
        );

        return $record ? new $class($record, false) : false;
    }

    protected function insert(IStorage $storage = null) {
        $record = $this->toArray();
        $primary_key = static::getPrimaryKey();

        if (!isset($record[$primary_key]))
            throw new OrmError(get_class($this) .': Must set primary key value before save');

        if (!$storage) $storage = static::getStorage();

        $storage->insert(static::getCollection(), $record, array('safe' => true));
        return $record[$primary_key];
    }

    protected function update(IStorage $storage = null) {
        if (!$storage) $storage = static::getStorage();
        $storage->update(
            static::getCollection(),
            array(static::getPrimaryKey() => $this->id()),
            array('$set' => $this->toArray(/* only dirty */true)),
            array('safe' => true)
        );
        return true;
    }

    protected function delete(IStorage $storage = null) {
        if (!$storage) $storage = static::getStorage();
        $storage->remove(
            static::getCollection(),
            array(static::getPrimaryKey() => $this->id()),
            array('justOne' => true, 'safe' => true)
        );
        return true;
    }
}
