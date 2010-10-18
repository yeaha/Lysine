<?php
namespace Lysine\ORM\DataMapper;

use Lysine\IStorage;
use Lysine\Utils\Set;

/**
 * Mongodb数据映射关系封装
 *
 * @uses Mapper
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class MongoMapper extends Mapper {
    /**
     * 根据主键查询一条数据
     *
     * @param mixed $key
     * @param IStorage $storage
     * @access protected
     * @return array
     */
    protected function doFind($key, IStorage $storage = null) {
        $meta = $this->getMeta();
        return $this->getStorage()->findOne(
            $meta->getCollection(),
            array($meta->getPrimaryKey() => $key)
        );
    }

    /**
     * 保存一条新数据
     * 返回主键值
     *
     * @param array $record
     * @param IStorage $storage
     * @access protected
     * @return mixed
     */
    protected function doInsert(array $record, IStorage $storage = null) {
        $meta = $this->getMeta();
        $primary_key = $meta->getPrimaryKey();
        if (!isset($record[$primary_key]))
            throw new \LogicException($this->class .': Must set primary key value before save');

        $this->getStorage()->insert(
            $meta->getCollection(),
            $record,
            array('safe' => true)
        );
        return $record[$primary_key];
    }

    /**
     * 更新一条数据
     *
     * @param mixed $id
     * @param array $record
     * @param IStorage $storage
     * @access protected
     * @return boolean
     */
    protected function doUpdate($id, array $record, IStorage $storage = null) {
        $meta = $this->getMeta();
        $this->getStorage()->update(
            $meta->getCollection(),
            array($meta->getPrimaryKey() => $id),
            array('$set' => $record),
            array('safe' => true)
        );
        return true;
    }

    /**
     * 删除指定主键的数据
     *
     * @param mixed $id
     * @param IStorage $storage
     * @access protected
     * @return boolean
     */
    protected function doDelete($id, IStorage $storage = null) {
        $meta = $this->getMeta();
        $this->getStorage()->remove(
            $meta->getCollection(),
            array($meta->getPrimaryKey() => $id),
            array('justOne' => true, 'safe' => true)
        );
        return true;
    }

    /**
     * 根据查询返回模型实例集合
     *
     * @param array $query
     * @access public
     * @return Lysine\Utils\Set
     */
    public function findByQuery(array $query) {
        $meta = $this->getMeta();
        $cur = $this->getStorage()->find(
            $meta->getCollection(),
            $query
        );

        $instances = array();
        while ($record = $cur->getNext()) {
            $instance = $this->package($record);
            $instances[$instance->id()] = $instance;
        }

        return new Set($instances);
    }
}
