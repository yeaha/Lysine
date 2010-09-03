<?php
namespace Lysine\Orm\ActiveRecord;

use Lysine\IStorage;
use Lysine\Storage\DB\IAdapter;
use Lysine\Storage\Pool;
use Lysine\Orm\ActiveRecord;

abstract class DB extends ActiveRecord {
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
        ),
        */
    );

    protected $referer = array();

    public function __get($key) {
        $val = parent::__get($key);
        if ($val !== false) return $val;

        if (array_key_exists($key, static::$referer))
            return $this->getReferer($key);

        return false;
    }

    public function save($refresh = true) {
        $pk = static::$primary_key;
        $table_name = static::$collection;
        $adapter = $this->getStorage();

        $row = $this->row;
        // 没有任何字段被改动过，而且主键值不为空
        // 说明这是从数据库中获得的数据，而且没改过，不需要保存
        if (!$this->dirty_row && isset($row[$pk]) && !$row[$pk]) return $this;

        $this->fireEvent('before save');

        if ($row[$pk]) {
            $method = 'update';
            // 有被改动过的主键值
            // 说明是新建数据，然后指定的主键，需要insert
            // 这个类的主键是不允许update的
            // 所以不可能出现主键值被改了，需要update的情况
            if (in_array($pk, $this->dirty_row)) $method = 'insert';
        } else {
            // 没有主键值，肯定是新建数据，需要insert
            $method = 'insert';
        }

        if ($method == 'insert') {
            $this->fireEvent('before insert');

            if ($affected = $adapter->insert($table_name, $this->row)) {
                if (!isset($row[$pk]))
                    $this->set($pk, $adapter->lastId($table_name, $pk), /* direct */true);

                $this->fireEvent('after insert');
            }
        } else {
            $this->fireEvent('before update');

            $col = $adapter->qcol($pk);
            $affected = $adapter()->update($table_name, $row, "{$col} = ?", $row[$pk]);

            if ($affected) $this->fireEvent('after update');
        }

        $this->fireEvent('after save');

        if ($refresh AND $affected) $this->refresh();
        return $this;
    }

    public function destroy() {
        if (!$id = $this->id()) return false;

        $this->fireEvent('before destroy');

        $adpater = $this->getStorage();
        $pk = $adapter->qcol(static::$primary_key);
        if ($affected = $adapter->delete(static::$collection, "{$pk} = ?", $id)) {
            $this->row = $this->dirty_row = $this->referer = array();
            $this->storage = null;
        }

        $this->fireEvent('after destroy');

        return $affected;
    }

    public function refresh() {
        if (!$id = $this->id()) return $this;

        $this->fireEvent('before refresh');

        $adapter = $this->getStorage();

        $sql = sprintf(
            'select * from %s where %s = ?',
            $adapter->qtab(static::$collection),
            $adapter->qcol(static::$primary_key)
        );

        $row = $adapter->execute($sql, $id)->getRow();

        if ($row) {
            $this->row = $row;
            $this->dirty_row = array();
            $this->referer = array();

            $this->fireEvent('after refresh');
        }
        return $this;
    }

    protected function getReferer($name) {
        if (array_key_exists($name, $this->referer))
            return $this->referer[$name];

        $config = static::$referer_config[$name];
        if (!isset($config['class']))
            throw new \UnexpectedValueException(__CLASS__ .': ActiveRecord referer must set class');

        $class = $config['class'];
        if (!is_subclass_of($class, 'Lysine\Orm\ActiveRecord\DB'))
            throw new \UnexpectedValueException(__CLASS__ .': Activerecord referer class must be subclass of Lysine\Orm\ActiveRecord\DB');

        $select = forward_static_call(array($class, 'select'));
        $adapter = $select->getAdapter();

        if (!isset($config['source_key'], $config['target_key']))
            throw new \UnexpectedValueException(__CLASS__ .': MUST specify activerecord referer source_key AND target_key');

        $target_key = $adapter->qcol($config['target_key']);
        $select->where("{$target_key} = ?", $this->get($config['source_key']));

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

    static public function select(IAdapter $adapter = null) {
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
        $select = static::select($storage);

        $pk = static::$primary_key;
        if (is_array($key)) {
            return $select->whereIn($pk, $key)->get();
        } else {
            return $select->where("{$pk} = ?", $key)->get(1);
        }
    }
}
