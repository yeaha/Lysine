<?php
namespace Lysine\ORM\DataMapper;

use Lysine\IStorage;
use Lysine\ORM\DataMapper\Data;

/**
 * 数据库映射关系封装
 *
 * @uses Mapper
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class DBMapper extends Mapper {
    /**
     * 根据主键从数据库查询数据
     *
     * @param mixed $id
     * @param IStorage $storage
     * @access protected
     * @return array
     */
    protected function doFind($id, IStorage $storage = null, $collection = null) {
        $meta = $this->getMeta();
        $adapter = $storage ?: $this->getStorage();
        $table_name = $adapter->qtab($collection ?: $meta->getCollection());
        $primary_key = $adapter->qcol($meta->getPrimaryKey());

        return $adapter->execute("SELECT * FROM {$table_name} WHERE {$primary_key} = ?", $id)->getRow();
    }

    /**
     * 插入新数据到数据库
     *
     * @param Data $data
     * @param IStorage $storage
     * @access protected
     * @return mixed 新主键
     */
    protected function doInsert(Data $data, IStorage $storage = null, $collection = null) {
        $record = $this->propsToRecord($data->toArray());

        $meta = $this->getMeta();
        $adapter = $storage ?: $this->getStorage();
        $table_name = $collection ?: $meta->getCollection();
        $primary_key = $meta->getPrimaryKey();

        if (!$adapter->insert($table_name, $record)) return false;

        if (isset($record[$primary_key])) return $record[$primary_key];
        return $adapter->lastId($table_name, $primary_key);
    }

    /**
     * 更新数据到数据库
     *
     * @param Data $data
     * @param IStorage $storage
     * @access protected
     * @return integer affected row count
     */
    protected function doUpdate(Data $data, IStorage $storage = null, $collection = null) {
        $record = $this->propsToRecord($data->toArray());

        $meta = $this->getMeta();
        $adapter = $storage ?: $this->getStorage();
        $table_name = $collection ?: $meta->getCollection();
        $primary_key = $adapter->qcol($meta->getPrimaryKey());

        return $adapter->update($table_name, $record, "{$primary_key} = ?", $data->id());
    }

    /**
     * 从数据库里删除领域模型数据
     *
     * @param Data $data
     * @param IStorage $storage
     * @access public
     * @return integer affected row count
     */
    protected function doDelete(Data $data, IStorage $storage = null, $collection = null) {
        $meta = $this->getMeta();
        $adapter = $storage ?: $this->getStorage();
        $table_name = $collection ?: $meta->getCollection();
        $primary_key = $adapter->qcol($meta->getPrimaryKey());

        return $adapter->delete($table_name, "{$primary_key} = ?", $data->id());
    }

    /**
     * 发起数据库查询
     *
     * @param IStorage $storage
     * @access public
     * @return Lysine\Storage\DB\Select
     */
    public function select(IStorage $storage = null, $collection = null) {
        $mapper = $this;
        $processor = function($row) use ($mapper) {
            return $row ? $mapper->package($row) : false;
        };

        $meta = $this->getMeta();
        $adapter = $storage ?: $this->getStorage();
        $select = $adapter->select($collection ?: $meta->getCollection())
                          ->setKeyColumn($meta->getPrimaryKey())
                          ->setProcessor($processor);
        return $select;
    }
}
