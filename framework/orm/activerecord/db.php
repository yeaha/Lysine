<?php
namespace Lysine\ORM\ActiveRecord;

use Lysine\IStorage;
use Lysine\Storage\DB\IAdapter;
use Lysine\Storage\Pool;
use Lysine\ORM\ActiveRecord;

/**
 * 数据库数据和业务模型映射封装
 *
 * @uses ActiveRecord
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class DBActiveRecord extends ActiveRecord {
    /*
    static protected $referer_config = array(
        'author' => array(
            'class' => 'Author',
            'source_field' => 'author_id',
            'target_field' => 'id',
            'where' => 'is_deleted = 0',
            'limit' => 1
        ),
        'books' => array(
            'class' => 'Book',
            'source_field' => 'id',
            'target_field' => 'author_id',
            'where' => array('is_deleted = ?', 0),
            'order' => 'create_time DESC',
        ),
    );
    */

    /**
     * 保存新数据
     * 返回新主键
     *
     * @access protected
     * @return mixed
     */
    protected function insert() {
        $pk = static::$primary_key;
        $table_name = static::$collection;
        $adapter = $this->getStorage();
        $record = $this->toArray();

        if ($adapter->insert($table_name, $record)) {
            $id = isset($record[$pk])
                ? $record[$pk]
                : $adapter->lastId($table_name, $pk);
            return $id;
        }

        return false;
    }

    /**
     * 更新数据
     *
     * @access protected
     * @return boolean
     */
    protected function update() {
        $table_name = static::$collection;
        $pk = $adapter->qcol(static::$primary_key);
        $record = $this->toArray(/* only dirty */true);

        return $adapter()->update($table_name, $record, "{$pk} = ?", $this->id());
    }

    /**
     * 从数据库中删除数据
     *
     * @access public
     * @return boolean
     */
    public function delete() {
        $adpater = $this->getStorage();
        $pk = $adapter->qcol(static::$primary_key);
        return $adapter->delete(static::$collection, "{$pk} = ?", $this->id());
    }

    /**
     * 获得关联数据
     * 只能在Lysine\ORM\ActiveRecord\DBActiveRecord类之间关联
     *
     * @param string $name
     * @access protected
     * @return mixed
     */
    protected function getReferer($name) {
        if (array_key_exists($name, $this->referer))
            return $this->referer[$name];

        $config = static::$referer_config[$name];
        if (!isset($config['class']))
            throw new \UnexpectedValueException(get_class($this) .': ActiveRecord referer must set class');

        $class = $config['class'];
        if (!class_exists($class))
            throw new \UnexpectedValueException(get_class($this) .': Undefined referer class ['. $class .']');

        if (!is_subclass_of($class, 'Lysine\ORM\ActiveRecord\DBActiveRecord'))
            throw new \UnexpectedValueException(get_class($this) .': Activerecord referer class ['. $class .'] must be subclass of Lysine\ORM\ActiveRecord\DB');

        $select = forward_static_call(array($class, 'select'));
        $adapter = $select->getAdapter();

        if (!isset($config['source_field'], $config['target_field']))
            throw new \UnexpectedValueException(get_class($this) .': MUST specify activerecord referer source_field AND target_field');

        $target_field = $adapter->qcol($config['target_field']);
        $select->where("{$target_field} = ?", $this->get($config['source_field']));

        if (isset($config['where'])) {
            if (is_array($config['where'])) {
                call_user_func_array(array($select, 'where'), $config['where']);
            } else {
                $select->where($config['where']);
            }
        }

        if (isset($config['limit'])) $select->limit($config['limit']);
        if (isset($config['order'])) $select->order($config['order']);

        $result = $select->get();
        $this->referer[$name] = $result;
        return $result;
    }

    /**
     * 生成数据库查询
     *
     * @param IAdapter $adapter
     * @static
     * @access public
     * @return Lysine\Storage\DB\Select
     */
    static public function select(IAdapter $adapter = null) {
        if (!$adapter) $adapter = static::getStorage();

        $class = get_called_class();
        $processor = function($record) use ($class, $adapter) {
            $ar = $record ? new $class($record, false) : new $class;
            $ar->setStorage($adapter);
            return $ar;
        };

        $select = $adapter->select(static::$collection)
                          ->setProcessor($processor)
                          ->setKeyColumn(static::$primary_key);
        return $select;
    }

    /**
     * 根据主键得到实例
     *
     * @param mixed $key
     * @param IStorage $storage
     * @static
     * @access public
     * @return mixed
     */
    static public function find($key, IStorage $storage = null) {
        $select = static::select($storage);

        $pk = static::$primary_key;
        if (is_array($key)) {
            return $select->whereIn($pk, $key)->get();
        } else {
            return $select->where("{$pk} = ?", $key)->get(1);
        }
    }
}
