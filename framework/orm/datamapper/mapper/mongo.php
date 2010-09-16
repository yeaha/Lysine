<?php
namespace Lysine\ORM\DataMapper;

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
     * mongodb collection连接实例
     *
     * @var MongoCollection
     * @access private
     */
    private $collection;

    /**
     * 获得当前模型的MongoCollection实例
     *
     * @access public
     * @return MongoCollection
     */
    public function getCollection() {
        if ($this->collection) return $this->collection;

        $explode = explode('.', $this->getMeta()->getCollection());
        if (count($explode) != 2)
            throw new \UnexpectedValueException($this->class .': Invalid collection meta');

        $mongo = $this->getStorage();
        if (!$mongo->connected) $mongo->connect();

        list($db, $collection) = $explode;
        $this->collection = $mongo->selectCollection($db, $collection);

        return $this->collection;
    }

    /**
     * 根据主键查询一条数据
     *
     * @param mixed $key
     * @access protected
     * @return array
     */
    protected function doFind($key) {
        $primary_key = $this->getMeta()->getPrimaryKey();
        return $this->getCollection()->findOne(array($primary_key => $key));
    }

    /**
     * 保存一条新数据
     * 返回主键值
     *
     * @param array $record
     * @access protected
     * @return mixed
     */
    protected function doPut(array $record) {
        $primary_key = $this->getMeta()->getPrimaryKey();
        if (!isset($record[$primary_key]))
            throw new \LogicException($this->class .': Must set primary key value before save');

        $this->getCollection()->insert($record, array('safe' => true));
        return $record[$primary_key];
    }

    /**
     * 更新一条数据
     *
     * @param mixed $id
     * @param array $record
     * @access protected
     * @return boolean
     */
    protected function doReplace($id, array $record) {
        $primary_key = $this->getMeta()->getPrimaryKey();
        $record = array('$set' => $record);
        $this->getCollection()->update(array($primary_key => $id), $record, array('safe' => true));
        return true;
    }

    /**
     * 删除指定主键的数据
     *
     * @param mixed $id
     * @access protected
     * @return boolean
     */
    protected function doDelete($id) {
        $primary_key = $this->getMeta()->getPrimaryKey();
        $this->getCollection()->remove(array($primary_key => $id), array('justOne' => true, 'safe' => true));
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
        $cur = $this->getCollection()->find($query);

        $instances = array();
        while ($record = $cur->getNext()) {
            $instance = $this->package($record);
            $instances[$instance->id()] = $instance;
        }

        return new Set($instances);
    }
}
