<?php
namespace Lysine\ORM\DataMapper;

use Lysine\IStorage;
use Lysine\ORM\DataMapper\Data;
use Lysine\ORMError;

class RedisMapper extends Mapper {
    /**
     * 把主键值转换为redis实际存储用的key
     * 可以通过重载这个方法实现不同数据的key转换
     *
     * @param mixed $id
     * @access protected
     * @return string
     */
    public function getStorageKey($id) {
        return ($id instanceof Data) ? $id->id() : $id;
    }

    protected function doFind($id, IStorage $storage = null, $collection = null) {
        $meta = $this->getMeta();
        $redis = $storage ?: $this->getStorage();
        $primary_key = $meta->getPrimaryKey();

        $record = $redis->hGetAll($this->getStorageKey($id));
        $record[$primary_key] = $id;
        return $record;
    }

    protected function doInsert(Data $data, IStorage $storage = null, $collection = null) {
        if ($this->doUpdate($data, $storage))
            return $data->id();
        return false;
    }

    protected function doUpdate(Data $data, IStorage $storage = null, $collection = null) {
        $meta = $this->getMeta();
        $redis = $storage ?: $this->getStorage();
        $primary_key = $meta->getPrimaryKey();

        $record = $this->propsToRecord($data->toArray());
        $id = $record[$primary_key];
        unset($record[$primary_key]);

        return $redis->hMSet($this->getStorageKey($id), $record);
    }

    protected function doDelete(Data $data, IStorage $storage = null, $collection = null) {
        $redis = $storage ?: $this->getStorage();
        return $redis->hDel($this->getStorageKey($data->id()));
    }
}
