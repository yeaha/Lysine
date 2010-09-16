<?php
namespace Lysine\ORM\ActiveRecord;

use Lysine\IStorage;
use Lysine\Storage\Pool;
use Lysine\ORM\ActiveRecord;

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
     * MongoCollection连接实例
     *
     * @var MongoCollection
     * @access private
     */
    private $coll;

    /**
     * 获得模型对应的MongoCollection连接实例
     *
     * @access public
     * @return MongoCollection
     */
    public function getCollection() {
        if ($this->coll) return $this->coll;

        list($db, $collection) = self::parseCollection();
        $this->coll = $this->getStorage()->selectCollection($db, $collection);

        return $this->coll;
    }

    /**
     * 保存新数据
     *
     * @access protected
     * @return mixed
     */
    protected function put() {
        $record = $this->toArray();
        $primary_key = static::$primary_key;

        if (!isset($record[$primary_key]))
            throw new \LogicException($this->class .': Must set primary key value before save');

        $this->getCollection()->insert($record, array('safe' => true));
        return $record[$primary_key];
    }

    /**
     * 更新数据
     *
     * @access protected
     * @return boolean
     */
    protected function replace() {
        $record = $this->toArray();
        $primary_key = static::$primary_key;
        $this->getCollection()->update(array($primary_key => $id), $record, array('safe' => true));
        return true;
    }

    /**
     * 删除数据
     *
     * @access protected
     * @return boolean
     */
    protected function delete() {
        $primary_key = static::$primary_key;
        $this->getCollection()->remove(array($primary_key => $this->id()), array('justOne' => true, 'safe' => true));
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
        $primary_key = static::$primary_key;
        list($db, $collection) = self::parseCollection();

        $storage = static::getStorage();
        if (!$record = $storage->selectCollection($db, $collection)->findOne(array($primary_key => $key)))
            return false;

        $class = get_called_class();
        $ar = new $class($record, false);
        $ar->setStorage($storage);
        return $ar;
    }

    /**
     * 解析mongo collection名字配置
     *
     * @static
     * @access private
     * @return array
     */
    static private function parseCollection() {
        $explode = explode('.', static::$collection);
        if (count($explode) != 2)
            throw new \UnexpectedValueException(get_class($this) .': Invalid collection ['. static::$collection .']');

        return $explode;
    }
}
