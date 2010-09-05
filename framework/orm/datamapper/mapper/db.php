<?php
namespace Lysine\ORM\DataMapper;

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
     * 根据主键从数据库实例化领域模型
     *
     * @param mixed $key
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    public function find($key) {
        $select = $this->select();
        $pk = $this->getMeta()->getPrimaryKey();

        if (is_array($key)) {
            return $select->whereIn($pk, $key)->get();
        } else {
            return $select->where($pk, $key)->get(1);
        }
    }

    /**
     * 保存新的领域模型数据到数据库
     *
     * @param Data $data
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    public function put(Data $data) {
        $record = $this->propsToRecord($data->toArray());
        $meta = $this->getMeta();
        $table_name = $meta->getCollection();
        $primary_key = $meta->getPrimaryKey();
        $adapter = $this->getStorage();

        if ($adapter->insert($table_name, $record)) {
            if (!$id = $data->id()) $id = $adapter->lastId($table_name, $primary_id);
            $record = $adapter->select($table_name)->where("{$primary_key} = ?", $id)->getRow();
            $data->__fill($this->recordToProps($record));
        }
        return $data;
    }

    /**
     * 更新领域模型数据到数据库
     *
     * @param Data $data
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    public function replace(Data $data) {
        $props = $data->toArray(/* only_dirty */true);
        if (!$props) return $data;

        $record = $this->propsToRecord($props);
        $meta = $this->getMeta();
        $table_name = $meta->getCollection();
        $primary_key = $meta->getPrimaryKey();

        $this->getStorage()->update($table_name, $record, "{$primary_key} = ?", $data->id());
        return $data;
    }

    /**
     * 从数据库里删除领域模型数据
     *
     * @param Data $data
     * @access public
     * @return boolean
     */
    public function delete(Data $data) {
        $meta = $this->getMeta();
        $table_name = $meta->getCollection();
        $primary_key = $meta->getPrimaryKey();

        return $this->getStorage()->delete($table_name, "{$primary_key} = ?", $data->id());
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
