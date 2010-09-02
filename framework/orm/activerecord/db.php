<?php
namespace Lysine\Orm\ActiveRecord;

use Lysine\IStorage;
use Lysine\Storage\Db\IAdapter;
use Lysine\Storage\Pool;
use Lysine\Orm\ActiveRecord;

abstract class Db extends ActiveRecord {
    static protected $referer_config = array(
        /*
        'author' => array(
            'class' => 'Author',
            'source_key' => 'author_id',
            'target_key' => 'id',
            'where' => 'is_deleted = 0',
            'limit' => 1
        ),
        'books' => array(
            'class' => 'Book',
            'source_key' => 'id',
            'target_key' => 'author_id',
            'where' => array('is_deleted = ?', 0),
            'order' => 'create_time DESC',
            'dispatcher' => array(
                'group' => 'book',
                'by_column' => 'id', // string or array，这里的字段指当前实例的字段
            ),
        ),
        'orders' => array(
            'getter' => 'getOrders',
        ),
        */
    );

    protected $referer = array();

    public function save() {
    }

    public function destroy() {
    }

    public function refresh() {
    }

    protected function getRefererStorage($name) {
    }

    protected function getReferer($name) {
    }

    static public function select(IAdapter $adapter = null) {
        // 如果设置adapter_config是dispatch方式，这里肯定会抛出一个异常
        if (!$adapter) $adapter = static::getStorage();

        $class = get_called_class();
        $processor = function($row) use ($class, $adapter) {
            $ar = $row ? new $class($row, true) : new $class(array(), false);
            $ar->setStorage($adapter);
            return $ar;
        };

        $select = $adapter->select(static::$collection)
                          ->setProcessor($processor)
                          ->setKeyColumn(static::$primary_key);
        return $select;
    }

    static public function find($key, IStorage $storage = null) {
        // 如果是多个主键，并且adapter_config是dispatch方式(垂直切分)
        // 可能每个主键对应的adapter是不一样的
        // 如果没有指定adapter实例，就需要每个主键依次获取adapter
        // 然后分组查询，最后再合并
        // 逻辑复杂，不做处理，这里会抛出异常
        // 所以同时查询多个主键，并且垂直切分时，需要在查询前传递adapter实例
        // 要不然就每个主键分别调用find()
        if (!$storage) {
            if (is_array($id) && is_array(static::$storage_config))
                throw new \LogicException('Can not find multiple ID while adapter config is dispatcher');

            $storage = static::getStorage($key);
        }
        $select = static::select($storage);

        $pk = static::$primary_key;
        if (is_array($key)) {
            return $select->whereIn($pk, $key)->get();
        } else {
            return $select->where("{$pk} = ?", $key)->get(1);
        }
    }
}
