<?php
namespace Lysine\ORM\DataMapper;

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
     * @param mixed $key
     * @access protected
     * @return array
     */
    protected function doFind($key) {
        $meta = $this->getMeta();
        $adapter = $this->getStorage();
        $table_name = $adapter->qtab($meta->getCollection());
        $primary_key = $adapter->qcol($meta->getPrimaryKey());

        return $adapter->execute("SELECT * FROM {$table_name} WHERE {$primary_key} = ?", $key)->getRow();
    }

    /**
     * 插入新数据到数据库
     *
     * @param array $record
     * @access protected
     * @return mixed
     */
    protected function doPut(array $record) {
        $meta = $this->getMeta();
        $table_name = $meta->getCollection();
        $primary_key = $meta->getPrimaryKey();
        $adapter = $this->getStorage();

        if (!$adapter->insert($table_name, $record)) return false;

        if (isset($record[$primary_key])) return $record[$primary_key];
        return $adapter->lastId($table_name, $primary_key);
    }

    /**
     * 更新数据到数据库
     *
     * @param mixed $id
     * @param array $record
     * @access protected
     * @return boolean
     */
    protected function doReplace($id, array $record) {
        $meta = $this->getMeta();
        $adapter = $this->getStorage();
        $table_name = $meta->getCollection();
        $primary_key = $adapter->qcol($meta->getPrimaryKey());

        return $adapter->update($table_name, $record, "{$primary_key} = ?", $id);
    }

    /**
     * 从数据库里删除领域模型数据
     *
     * @param mixed $id
     * @access public
     * @return boolean
     */
    protected function doDelete($id) {
        $meta = $this->getMeta();
        $adapter = $this->getStorage();
        $table_name = $meta->getCollection();
        $primary_key = $adapter->qcol($meta->getPrimaryKey());

        return $adapter->delete($table_name, "{$primary_key} = ?", $id);
    }

    /**
     * 发起数据库查询
     *
     * @access public
     * @return Lysine\Storage\DB\Select
     */
    public function select() {
        $processor = array($this, 'package');

        $meta = $this->getMeta();
        $select = $this->getStorage()
                       ->select($meta->getCollection())
                       ->setKeyColumn($meta->getPrimaryKey())
                       ->setProcessor($processor);
        return $select;
    }
}