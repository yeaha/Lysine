<?php
namespace Lysine\ORM;

use Lysine\ORM;
use Lysine\ORM\Registry;
use Lysine\OrmError;
use Lysine\IStorage;
use Lysine\Storage;

interface IActiveRecord {
    static public function find($key, IStorage $storage = null);
}

abstract class ActiveRecord extends ORM implements IActiveRecord {
    static protected $storage;
    static protected $collection;
    static protected $primary_key;
    // 虚拟属性配置
    static protected $props_config = array(
        /*
        'orders' => array(
            'getter' => 'getOrders',
            'setter' => 'setOrders',
        ),
        'books' => array(
            'getter' => array('getBooks', true),  // 把第一次获取的结果缓存起来，不重复调用
        ),
        */
    );
    static protected $is_readonly = false;

    protected $is_fresh = true;
    protected $record = array();
    protected $dirty_record = array();
    // 虚拟属性
    protected $props = array();

    abstract protected function insert(IStorage $storage = null);
    abstract protected function update(IStorage $storage = null);
    abstract protected function delete(IStorage $storage = null);

    public function __construct(array $record = array(), $fresh = true) {
        if ($record) {
            $this->record = $record;
            $this->is_fresh = $fresh;

            if ($fresh) {
                $this->dirty_record = array_keys($record);
            } else {
                Registry::set($this);
            }
        }

        $this->fireEvent(ORM::AFTER_INIT_EVENT);
    }

    public function __destruct() {
        clear_event($this);
    }

    public function __set($key, $val) {
        if (static::$is_readonly) throw OrmError::readonly($this);

        if (isset(static::$props_config[$key]['setter'])) {
            $setter = static::$props_config[$key]['setter'];
            $this->$setter($val);
            unset($this->props[$key]);
        } else {
            $this->set($key, $val);
        }
    }

    public function __get($key) {
        if (isset(static::$props_config[$key]['getter'])) {
            if (isset($this->props[$key])) return $this->props[$key];

            $getter_config = static::$props_config[$key]['getter'];

            if (is_array($getter_config)) {
                $getter = array_shift($getter_config);
                $cache = array_shift($getter_config);
            } else {
                $getter = $getter_config;
                $cache = false;
            }

            $prop = $this->$getter();
            if ($cache) $this->props[$key] = $prop;
            return $prop;
        }

        return $this->get($key);
    }

    public function set($field, $val = null, $direct = false) {
        if (static::$is_readonly) throw OrmError::readonly($this);

        if (is_array($field)) {
            $values = $field;
            $direct = (bool)$val;
        } else {
            $values = array($field => $val);
        }

        if (!$this->is_fresh) {
            $primary_key = static::getPrimaryKey();
            if (isset($values[$primary_key]))
                throw OrmError::refuse_update($this, $primary_key);

            if ($fields = array_diff(array_keys($values), array_keys($this->record)))
                throw OrmError::undefined_property($this, implode(',', $fields));
        }

        foreach ($values as $field => $val) $this->record[$field] = $val;

        if (!$direct)
            $this->dirty_record = array_unique(array_merge($this->dirty_record, array_keys($values)));

        return $this;
    }

    public function get($field) {
        if (array_key_exists($field, $this->record))
            return $this->record[$field];

        if (!$this->is_fresh)
            throw OrmError::undefined_property($this, $field);

        return false;
    }

    public function id() {
        return $this->get(static::getPrimaryKey());
    }

    public function isDirty() {
        return (bool)array_keys($this->dirty_record);
    }

    public function isFresh() {
        return $this->is_fresh;
    }

    public function save() {
        if (static::$is_readonly) throw OrmError::readonly($this);

        if (!$this->isFresh() && !$this->isDirty()) return $this;

        $this->fireEvent(ORM::BEFORE_SAVE_EVENT);

        $saved = false;
        if ($this->isFresh()) {
            $this->fireEvent(ORM::BEFORE_INSERT_EVENT);
            if ($new_primary_key = $this->insert()) {
                $this->set(static::getPrimaryKey(), $new_primary_key);
                $this->fireEvent(ORM::AFTER_INSERT_EVENT);
                Registry::set($this);
                $saved = true;
            }
        } else {
            $this->fireEvent(ORM::BEFORE_UPDATE_EVENT);
            if ($affected = $this->update()) {
                $this->fireEvent(ORM::AFTER_UPDATE_EVENT);
                $saved = true;
            }
        }

        if ($saved) {
            $this->is_fresh = false;
            $this->dirty_record = $this->props = array();
            $this->fireEvent(ORM::AFTER_SAVE_EVENT);
        }

        return $this;
    }

    public function destroy() {
        if (static::$is_readonly) throw OrmError::readonly($this);

        if (!$this->isFresh()) return false;

        $this->fireEvent(ORM::BEFORE_DELETE_EVENT);
        if (!$this->delete()) return false;
        $this->fireEvent(ORM::AFTER_DELETE_EVENT);

        $this->record = $this->dirty_record = $this->props = array();
        return true;
    }

    public function toArray($only_dirty = false) {
        if (!$only_dirty) return $this->record;

        $record = array();
        foreach ($this->dirty_record as $field)
            $record[$field] = $this->record[$field];

        return $record;
    }

    static public function getStorage($args = null) {
        if ($args = null) return Storage\Pool::instance()->get(static::$storage);

        $args = is_array($args) ? $args : func_get_args();
        array_unshift($args, static::$storage);
        return call_user_func_array(array(Storage\Pool::instance(), 'get'), $args);
    }

    static public function getPrimaryKey() {
        if ($primary_key = static::$primary_key) return $primary_key;
        throw OrmError::undefined_primarykey(get_called_class());
    }

    static public function getCollection() {
        if ($collection = static::$collection) return $collection;
        throw OrmError::undefined_collection(get_called_class());
    }
}
