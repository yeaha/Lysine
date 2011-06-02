<?php
namespace Lysine\DataMapper;

use Lysine\IStorage;
use Lysine\DataMapper\Data;

/**
 * 使用数据库存储方式的领域模型
 *
 * @uses Data
 * @abstract
 * @package DataMapper
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class DBData extends Data {
    protected function formatProp($prop, $val, array $prop_meta) {
        $val = parent::formatProp($prop, $val, $prop_meta);

        if ($prop_meta['allow_null']) {
            if ($prop_meta['default'] === $val)
                return null;
        } else {
            if ($val === null)
                return $prop_meta['default'];
        }

        return $val;
    }

    /**
     * 获得数据映射关系封装
     *
     * @static
     * @access public
     * @return void
     */
    static public function getMapper() {
        return DBMapper::factory(get_called_class());
    }

    /**
     * 发起数据库查询
     *
     * @static
     * @access public
     * @return Lysine\Storage\DB\Select
     */
    static public function select() {
        return static::getMapper()->select();
    }
}

/**
 * 数据库映射关系封装
 *
 * @uses Mapper
 * @package DataMapper
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
        $record = $this->propsToRecord($data->toArray($only_dirty = true));

        $meta = $this->getMeta();
        $adapter = $storage ?: $this->getStorage();
        $table_name = $collection ?: $meta->getCollection();

        $primary_key = $meta->getPrimaryKey();
        unset($record[$primary_key]);
        $primary_key = $adapter->qcol($primary_key);

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
