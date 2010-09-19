<?php
namespace Lysine\ORM\ActiveRecord;

use Lysine\IStorage;
use Lysine\Storage\Pool;
use Lysine\ORM\ActiveRecord;
use Lysine\ORM\Registry;

/**
 * Mongo数据AR模式封装
 *
 * @uses ActiveRecord
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class MongoActiveRecord extends ActiveRecord {
    /**
     * 保存新数据
     *
     * @access protected
     * @return mixed
     */
    protected function insert() {
        $record = $this->toArray();
        $primary_key = static::$primary_key;

        if (!isset($record[$primary_key]))
            throw new \LogicException($this->class .': Must set primary key value before save');

        $this->getStorage()->insert(
            static::$collection,
            $record,
            array('safe' => true)
        );
        return $record[$primary_key];
    }

    /**
     * 更新数据
     *
     * @access protected
     * @return boolean
     */
    protected function update() {
        $this->getStorage()->update(
            static::$collection,
            array(static::$primary_key => $this->id()),
            array('$set' => $this->toArray(/* only dirty */true)),
            array('safe' => true)
        );
        return true;
    }

    /**
     * 删除数据
     *
     * @access protected
     * @return boolean
     */
    protected function delete() {
        $this->getStorage()->remove(
            static::$collection,
            array(static::$primary_key => $this->id()),
            array('justOne' => true, 'safe' => true)
        );
        return true;
    }

    /**
     * 获得关联数据
     *
     * @param string $name
     * @access protected
     * @return mixed
     */
    protected function getReferer($name) {
    }

    /**
     * 根据主键生成实例
     *
     * @param mixed $key
     * @param IStorage $storage
     * @static
     * @access public
     * @return Lysine\ORM\ActiveRecord
     */
    static public function find($key, IStorage $storage = null) {
        $class = get_called_class();
        if ($ar = Registry::get($class, $key)) return $ar;

        if (!$storage) $storage = static::getStorage();
        $record = $storage->findOne(
            static::$collection,
            array(static::$primary_key => $key)
        );

        if (!$record) return false;

        $ar = new $class($record, false);
        $ar->setStorage($storage);
        return $ar;
    }
}
