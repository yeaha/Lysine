<?php
namespace Lysine\ORM;

use Lysine\ORM;
use Lysine\ORM\Registry;
use Lysine\OrmError;
use Lysine\IStorage;
use Lysine\Storage;

interface IActiveRecord {
    /**
     * 根据主键值查询出模型实例
     *
     * @param mixed $key
     * @param IStorage $storage
     * @static
     * @access public
     * @return ActiveRecord
     */
    static public function find($key, IStorage $storage = null);
}

/**
 * 活动记录模型封装
 *
 * 这个ActiveRecord实现没有包括数据关系映射功能（一对一，一对多）
 * 主要是考虑到不同存储服务的ActiveRecord之间无法实现常见的映射方式
 * 所以只实现了setter getter机制，通过定义虚拟属性自行实现关系映射
 *
 * @uses ORM
 * @uses IActiveRecord
 * @abstract
 * @package ActiveRecord
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class ActiveRecord extends ORM implements IActiveRecord {
    /**
     * 存储服务名字
     *
     * @see Lysine\Storage\Pool
     * @var string
     * @access protected
     * @static
     */
    static protected $storage;

    /**
     * 存储集合名字
     *
     * @var string
     * @access protected
     * @static
     */
    static protected $collection;

    /**
     * 主键
     *
     * @var string
     * @access protected
     * @static
     */
    static protected $primary_key;

    /**
     * 虚拟属性
     *
     * @var array
     * @access protected
     * @static
     */
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

    /**
     * 是否只读模型
     *
     * @var boolean
     * @access protected
     * @static
     */
    static protected $is_readonly = false;

    /**
     * 是否全新的模型 没有存储过
     *
     * @var boolean
     * @access protected
     */
    protected $is_fresh = true;

    /**
     * 模型数据
     *
     * @var array
     * @access protected
     */
    protected $record = array();

    /**
     * 被修改过的数据字段名列表
     *
     * @var array
     * @access protected
     */
    protected $dirty_record = array();

    /**
     * 虚拟属性缓存
     *
     * @var array
     * @access protected
     */
    protected $props = array();

    /**
     * 保存新数据到存储服务
     *
     * @param IStorage $storage
     * @abstract
     * @access protected
     * @return mixed 新数据的主键值
     */
    abstract protected function insert(IStorage $storage = null);

    /**
     * 更新数据到存储服务
     *
     * @param IStorage $storage
     * @abstract
     * @access protected
     * @return integer affected row count
     */
    abstract protected function update(IStorage $storage = null);

    /**
     * 从存储服务删除数据
     *
     * @param IStorage $storage
     * @abstract
     * @access protected
     * @return integer affected row count
     */
    abstract protected function delete(IStorage $storage = null);

    /**
     * 构造函数
     *
     * @param array $record
     * @param boolean $fresh
     * @access public
     * @return void
     */
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

    /**
     * 析构函数
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        clear_event($this);
    }

    /**
     * 魔法方法
     *
     * @param string $key
     * @param mixed $val
     * @access public
     * @return void
     */
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

    /**
     * 魔法方法
     *
     * @param string $key
     * @access public
     * @return mixed
     */
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

    /**
     * 修改模型数据
     *
     * @param mixed $field
     * @param mixed $val
     * @param boolean $direct
     * @access public
     * @return ActiveRecord
     */
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

    /**
     * 获得指定字段的数据
     *
     * @param string $field
     * @access public
     * @return mixed
     */
    public function get($field) {
        if (array_key_exists($field, $this->record))
            return $this->record[$field];

        if (!$this->is_fresh)
            throw OrmError::undefined_property($this, $field);

        return false;
    }

    /**
     * 主键值
     *
     * @access public
     * @return mixed
     */
    public function id() {
        return $this->get(static::getPrimaryKey());
    }

    /**
     * 是否被修改过
     *
     * @access public
     * @return boolean
     */
    public function isDirty() {
        return (bool)array_keys($this->dirty_record);
    }

    /**
     * 是否保存过
     *
     * @access public
     * @return boolean
     */
    public function isFresh() {
        return $this->is_fresh;
    }

    /**
     * 保存模型数据
     *
     * @access public
     * @return ActiveRecord
     */
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

    /**
     * 从存储服务中删除模型数据
     *
     * @access public
     * @return boolean
     */
    public function destroy() {
        if (static::$is_readonly) throw OrmError::readonly($this);

        if (!$this->isFresh()) return false;

        $this->fireEvent(ORM::BEFORE_DELETE_EVENT);
        if (!$this->delete()) return false;
        $this->fireEvent(ORM::AFTER_DELETE_EVENT);

        $this->record = $this->dirty_record = $this->props = array();
        return true;
    }

    /**
     * 转换为数组格式
     *
     * @param boolean $only_dirty 只包括修改过的内容
     * @access public
     * @return array
     */
    public function toArray($only_dirty = false) {
        if (!$only_dirty) return $this->record;

        $record = array();
        foreach ($this->dirty_record as $field)
            $record[$field] = $this->record[$field];

        return $record;
    }

    /**
     * 获得模型对应的存储服务
     *
     * @param mixed $args
     * @static
     * @access public
     * @return IStorage
     */
    static public function getStorage($args = null) {
        if ($args = null) return Storage\Pool::instance()->get(static::$storage);

        $args = is_array($args) ? $args : func_get_args();
        array_unshift($args, static::$storage);
        return call_user_func_array(array(Storage\Pool::instance(), 'get'), $args);
    }

    /**
     * 获得主键名
     *
     * @static
     * @access public
     * @return string
     */
    static public function getPrimaryKey() {
        if ($primary_key = static::$primary_key) return $primary_key;
        throw OrmError::undefined_primarykey(get_called_class());
    }

    /**
     * 获得存储集合名
     *
     * @static
     * @access public
     * @return string
     */
    static public function getCollection() {
        if ($collection = static::$collection) return $collection;
        throw OrmError::undefined_collection(get_called_class());
    }
}
