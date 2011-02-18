<?php
namespace Lysine\ORM\DataMapper;

use Lysine\IStorage;
use Lysine\ORM\DataMapper\Data;
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
     * @param mixed $id
     * @param IStorage $storage
     * @access protected
     * @return array
     */
    protected function doFind($id, IStorage $storage = null, $collection = null) {
        $meta = $this->getMeta();
        return $meta->getStorage()->findOne(
            $meta->getCollection(),
            array($meta->getPrimaryKey() => $id)
        );
    }

    /**
     * 保存一条新数据
     * 返回主键值
     *
     * @param Data $data
     * @param IStorage $storage
     * @access protected
     * @return mixed 新主键
     */
    protected function doInsert(Data $data, IStorage $storage = null, $collection = null) {
        if (!$id = $data->id())
            throw new \LogicException($this->class .': Must set primary key value before save');

        $meta = $this->getMeta();
        $meta->getStorage()->insert(
            $meta->getCollection(),
            $this->propsToRecord($data->toArray()),
            array('safe' => true)
        );
        return $id;
    }

    /**
     * 更新一条数据
     *
     * @param Data $data
     * @param IStorage $storage
     * @access protected
     * @return boolean
     */
    protected function doUpdate(Data $data, IStorage $storage = null, $collection = null) {
        $meta = $this->getMeta();
        $meta->getStorage()->update(
            $meta->getCollection(),
            array($meta->getPrimaryKey() => $data->id()),
            array('$set' => $this->propsToRecord($data->toArray())),
            array('safe' => true)
        );
        return true;
    }

    /**
     * 删除指定主键的数据
     *
     * @param Data $data
     * @param IStorage $storage
     * @access protected
     * @return boolean
     */
    protected function doDelete(Data $data, IStorage $storage = null, $collection = null) {
        $meta = $this->getMeta();
        $meta->getStorage()->remove(
            $meta->getCollection(),
            array($meta->getPrimaryKey() => $data->id()),
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
        $cur = $meta->getStorage()->find(
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
