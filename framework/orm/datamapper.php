<?php
namespace Lysine\DataMapper;

use Lysine\Config;
use Lysine\Error;
use Lysine\IStorage;
use Lysine\OrmError;
use Lysine\Storage\Cache;
use Lysine\Storage\Manager;
use Lysine\Utils;

/**
 * 领域模型接口
 *
 * @package DataMapper
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
interface IData {
    /**
     * 获得领域模型数据映射关系封装实例
     *
     * @static
     * @access public
     * @return Lysine\DataMapper\Mapper
     */
    static public function getMapper();
}

/**
 * 领域模型基类
 *
 * @uses IData
 * @abstract
 * @package DataMapper
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class Data implements IData {
    // {{{ 内置事件
    const BEFORE_SAVE_EVENT = 'before save';
    const AFTER_SAVE_EVENT = 'after save';

    const BEFORE_INSERT_EVENT = 'before insert';
    const AFTER_INSERT_EVENT = 'after insert';

    const BEFORE_UPDATE_EVENT = 'before update';
    const AFTER_UPDATE_EVENT = 'after update';

    const BEFORE_DELETE_EVENT = 'before delete';
    const AFTER_DELETE_EVENT = 'after delete';
    // }}}

    // {{{ 内置事件响应方法
    protected function __before_save() {}
    protected function __after_save() {}

    protected function __before_insert() {}
    protected function __after_insert() {}

    protected function __before_update() {}
    protected function __after_update() {}

    protected function __before_delete() {}
    protected function __after_delete() {}
    // }}}

    // {{{ 事件关联方法
    static protected $event_methods = array(
        self::BEFORE_SAVE_EVENT => '__before_save',
        self::AFTER_SAVE_EVENT => '__after_save',

        self::BEFORE_INSERT_EVENT => '__before_insert',
        self::AFTER_INSERT_EVENT => '__after_insert',

        self::BEFORE_UPDATE_EVENT => '__before_update',
        self::AFTER_UPDATE_EVENT => '__after_update',

        self::BEFORE_DELETE_EVENT => '__before_delete',
        self::AFTER_DELETE_EVENT => '__after_delete',
    );
    // }}}

    static protected $storage;
    static protected $collection;
    static protected $readonly = false;
    static protected $props_meta = array();

    private $is_fresh = true;
    private $props = array();
    private $dirty_props = array();

    public function __construct(array $props = null, $is_fresh = true) {
        if ($props) $this->setProp($props);

        $this->is_fresh = $is_fresh;
        if (!$is_fresh) $this->dirty_props = array();
    }

    public function __destruct() {
        clear_event($this);
    }

    public function __fill(array $props) {
        $this->props = array_merge($this->props, $props);
        $this->is_fresh = false;
        $this->dirty_props = array();
        return $this;
    }

    public function __get($prop) {
        return $this->getProp($prop);
    }

    public function __set($prop, $val) {
        return $this->setProp($prop, $val);
    }

    public function hasProp($prop) {
        return (bool)static::getMeta()->getPropMeta($prop);
    }

    public function getProp($prop) {
        if (!$prop_meta = static::getMeta()->getPropMeta($prop))
            throw Error::undefined_property(get_class($this), $prop);

        $val = isset($this->props[$prop]) ? $this->props[$prop] : null;
        if ($val === null && $prop_meta['default'] !== null)
            return $prop_meta['default'];
        return $val;
    }

    public function setProp($prop, $val = null, $static = true) {
        if (static::$readonly) throw OrmError::readonly($this);

        if (is_array($prop)) {
            $props = $prop;
            $strict = ($val === null) ? true : (bool)$val;
        } else {
            $props = array($prop => $val);
        }

        $meta = static::getMeta();
        foreach ($props as $prop => $val) {
            if (!$prop_meta = $meta->getPropMeta($prop)) {
                if (!$strict) continue;
                throw Error::undefined_property(get_class($this), $prop);
            }

            if (!$this->is_fresh && ($prop_meta['refuse_update'] || $prop_meta['primary_key']))
                throw OrmError::refuse_update($this, $prop);

            $val = $this->formatProp($prop, $val, $prop_meta);

            if (!$prop_meta['allow_null'] && $val === null)
                throw OrmError::not_allow_null($this, $prop);

            $this->changeProp($prop, $val);
        }

        return $this;
    }

    protected function changeProp($prop, $val) {
        if (!isset($this->props[$prop])) {
            if ($val === null) return;
        } elseif ($val === $this->props[$prop]) {
            return;
        }

        $this->props[$prop] = $val;
        if (!in_array($prop, $this->dirty_props))
            $this->dirty_props[] = $prop;
    }

    protected function formatProp($prop, $val, array $prop_meta) {
        if ($val === null) return $val;
        if ($val === '') return null;

        $type = $prop_meta['type'];
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$val;
            case 'float':
            case 'real':
            case 'double':
                return (float)$val;
            case 'string':
            case 'text':
                return (string)$val;
        }

        return $val;
    }

    public function id() {
        $prop = static::getMeta()->getPrimaryKey($as_prop = true);
        return $this->getProp($prop);
    }

    public function isFresh() {
        return $this->is_fresh;
    }

    public function isDirty() {
        return (bool)$this->dirty_props;
    }

    public function isReadonly() {
        return static::$readonly;
    }

    public function toArray($only_dirty = false) {
        if (!$only_dirty) return $this->props;

        $props = array();
        foreach ($this->dirty_props as $prop)
            $props[$prop] = $this->props[$prop];
        return $props;
    }

    public function save() {
        if (static::$readonly) throw OrmError::readonly($this);
        return static::getMapper()->save($this);
    }

    public function destroy() {
        if (static::$readonly) throw OrmError::readonly($this);
        return static::getMapper()->destroy($this);
    }

    public function fireEvent($event, $args = null) {
        if (isset(self::$event_methods[$event])) {
            $method = self::$event_methods[$event];
            $this->$method();
        }

        $args = is_array($args) ? $args : array_slice(func_get_args(), 1);
        array_unshift($args, $this);
        return fire_event($this, $event, $args);
    }

    static public function getMetaDefine() {
        $meta = array(
            'storage' => static::$storage,
            'collection' => static::$collection,
            'props' => static::$props_meta
        );

        $called_class = get_called_class();
        if ($called_class == __CLASS__)
            return $meta;

        $parent_class = get_parent_class($called_class);
        $parent_meta = $parent_class::getMetaDefine();
        $meta['props'] = array_merge(
            $parent_meta['props'],
            $meta['props']
        );

        return $meta;
    }

    static public function getMeta() {
        return static::getMapper()->getMeta();
    }

    static public function find() {
        return call_user_func_array(array(static::getMapper(), 'find'), func_get_args());
    }
}

/**
 * 封装领域模型存储服务数据映射关系
 *
 * @abstract
 * @package DataMapper
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class Mapper {
    /**
     * 实例集合
     * 每个实例对应一个领域模型类
     */
    static private $instance = array();

    /**
     * 当前实例对应的领域模型类
     *
     * @var string
     * @access protected
     */
    protected $class;

    /**
     * 领域模型元数据
     *
     * @var Lysine\DataMapper\Meta
     * @access protected
     */
    protected $meta;

    /**
     * 根据主键查找数据
     *
     * @param mixed $id
     * @param IStorage $storage
     * @abstract
     * @access protected
     * @return array
     */
    abstract protected function doFind($id, IStorage $storage = null, $collection = null);

    /**
     * 保存数据到存储服务
     *
     * @param Data $data
     * @param IStorage $storage
     * @abstract
     * @access protected
     * @return mixed 新数据的主键值
     */
    abstract protected function doInsert(Data $data, IStorage $storage = null, $collection = null);

    /**
     * 保存更新数据到存储服务
     *
     * @param Data $data
     * @param IStorage $storage
     * @abstract
     * @access protected
     * @return boolean
     */
    abstract protected function doUpdate(Data $data, IStorage $storage = null, $collection = null);

    /**
     * 删除指定主键的数据
     *
     * @param Data $data
     * @param IStorage $storage
     * @abstract
     * @access protected
     * @return boolean
     */
    abstract protected function doDelete(Data $data, IStorage $storage = null, $collection = null);

    /**
     * 构造函数
     *
     * @param string $class
     * @access private
     * @return void
     */
    private function __construct($class) {
        $this->class = $class;
    }

    /**
     * 获得领域模型元数据封装
     *
     * @access public
     * @return Lysine\DataMapper\Meta
     */
    public function getMeta() {
        if (!$this->meta) $this->meta = Meta::factory($this->class);
        return $this->meta;
    }

    /**
     * 获得存储服务连接实例
     *
     * @access public
     * @return Lysine\IStorage
     */
    public function getStorage() {
        return Manager::instance()->get(
            $this->getMeta()->getStorage()
        );
    }

    /**
     * 把从存储中获得的数据转化为属性数组
     *
     * @param array $record
     * @access public
     * @return array
     */
    public function recordToProps(array $record) {
        $props = array();
        foreach ($this->getMeta()->getPropOfField() as $field => $prop) {
            if (isset($record[$field]))
                $props[$prop] = $record[$field];
        }
        return $props;
    }

    /**
     * 把属性数据转换为可以保存到存储服务的数据
     *
     * @param array $props
     * @access public
     * @return array
     */
    public function propsToRecord(array $props) {
        $field_of_prop = $this->getMeta()->getFieldOfProp();
        $record = array();
        foreach ($props as $prop => $val) {
            $field = $field_of_prop[$prop];
            $record[$field] = $val;
        }

        return $record;
    }

    /**
     * 根据主键返回模型
     *
     * @param mixed $id
     * @access public
     * @see \Lysine\DataMapper\Data::find
     * @return Lysine\DataMapper\Data
     */
    public function find(/* $id */) {
        list($id) = func_get_args();

        if ($data = Registry::get($this->class, $id)) return $data;

        if (!$record = $this->doFind($id)) return false;
        return $this->package($record);
    }

    /**
     * 保存模型数据到存储服务
     *
     * @param Data $data
     * @access public
     * @return Lysine\DataMapper\Data
     */
    public function save(Data $data) {
        if ($data->isReadonly())
            throw OrmError::readonly($data);

        if (!($is_fresh = $data->isFresh()) && !($is_dirty = $data->isDirty()))
            return true;

        $data->fireEvent(Data::BEFORE_SAVE_EVENT);
        if ($is_fresh) {
            $data->fireEvent(Data::BEFORE_INSERT_EVENT, $data);
        } elseif ($is_dirty) {
            $data->fireEvent(Data::BEFORE_UPDATE_EVENT, $data);
        }

        $props = $data->toArray();
        foreach ($this->getMeta()->getPropMeta() as $prop => $prop_meta) {
            if (!$prop_meta['allow_null'] && !isset($props[$prop]) && $prop_meta['default'] === null)
                throw OrmError::not_allow_null($data, $prop);
        }

        if ($is_fresh) {
            if ($result = $this->insert($data))
                $data->fireEvent(Data::AFTER_INSERT_EVENT);
        } elseif ($is_dirty) {
            if ($result = $this->update($data))
                $data->fireEvent(Data::AFTER_UPDATE_EVENT);
        }

        if ($result)
            $data->fireEvent(Data::AFTER_SAVE_EVENT);

        return $result;
    }

    /**
     * 保存新的模型数据到存储服务
     * 返回新主键
     *
     * @param Data $data
     * @access protected
     * @return mixed
     */
    protected function insert(Data $data) {
        try {
            if (!$id = $this->doInsert($data)) return false;
        } catch (\Exception $ex) {
            throw OrmError::insert_failed($data, $ex);
        }

        $primary_key = $this->getMeta()->getPrimaryKey();
        $record = array($primary_key => $id);
        $data->__fill($this->recordToProps($record));

        Registry::set($data);
        return $id;
    }

    /**
     * 更新领域模型数据到存储服务
     *
     * @param Data $data
     * @access protected
     * @return boolean
     */
    protected function update(Data $data) {
        try {
            if (!$this->doUpdate($data)) return false;
        } catch (\Exception $ex) {
            throw OrmError::update_failed($data, $ex);
        }
        $data->__fill($data->toArray());
        return true;
    }

    /**
     * 从存储服务删除模型数据
     *
     * @param Data $data
     * @access public
     * @return boolean
     */
    public function destroy(Data $data) {
        if ($data->isReadonly())
            throw OrmError::readonly($data);

        if ($data->isFresh()) return true;

        $data->fireEvent(Data::BEFORE_DELETE_EVENT);
        try {
            if (!$this->doDelete($data)) return false;
        } catch (\Exception $ex) {
            throw OrmError::delete_failed($data, $ex);
        }
        $data->fireEvent(Data::AFTER_DELETE_EVENT);
        Registry::remove($this->class, $data->id());
        return true;
    }

    /**
     * 把存储服务中获得的数据实例化为领域模型
     *
     * @param array $record
     * @access public
     * @return Lysine\DataMapper\Data
     */
    public function package(array $record) {
        $data_class = $this->class;
        $data = new $data_class;
        $data->__fill($this->recordToProps($record));

        Registry::set($data);
        return $data;
    }

    /**
     * 获得指定领域模型的映射关系实例
     *
     * @param mixed $class
     * @static
     * @access public
     * @return Lysine\DataMapper\Mapper
     */
    static public function factory($class) {
        if (!isset(self::$instance[$class]))
            self::$instance[$class] = new static($class);
        return self::$instance[$class];
    }
}

/**
 * 领域模型元数据
 *
 * @package DataMapper
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Meta {
    /**
     * 默认属性元数据定义数组
     */
    static private $default_prop_meta = array(
        'field' => NULL,            // 存储字段名
        'type' => NULL,             // 数据类型
        'primary_key' => FALSE,     // 是否主键
        'refuse_update' => FALSE,   // 是否允许更新
        'allow_null' => FALSE,      // 是否允许为空
        'default' => NULL,          // 默认值
    );

    static private $instance = array();

    private $class;
    private $storage;
    private $collection;
    private $primary_key;
    private $prop_meta;
    private $prop_to_field;
    private $field_to_prop;

    private function __construct($class) {
        $this->class = $class;
        $define = $class::getMetaDefine();
        $this->storage = $define['storage'];
        $this->collection = $define['collection'];

        $default = self::$default_prop_meta;
        foreach ($define['props'] as $prop_name => &$prop_meta) {
            $prop_meta = array_merge($default, $prop_meta);
            $prop_meta['field'] = $prop_meta['field'] ?: $prop_name;
            if ($prop_meta['primary_key']) $this->primary_key = $prop_meta['field'];
            $this->prop_to_field[$prop_name] = $prop_meta['field'];
        }

        if (!$this->primary_key)
            throw OrmError::undefined_primarykey($this->class);

        $this->field_to_prop = array_flip($this->prop_to_field);
        $this->prop_meta = $define['props'];
    }

    public function getStorage() {
        return $this->storage;
    }

    public function getCollection() {
        if (!$collection = $this->collection)
            throw OrmError::undefined_collection($this->class);
        return $collection;
    }

    public function getPrimaryKey($as_prop = false) {
        if (!$primary_key = $this->primary_key)
            throw OrmError::undefined_primarykey($this->class);

        return $as_prop ? $this->getPropOfField($primary_key) : $primary_key;
    }

    public function getPropMeta($prop = null) {
        if ($prop === null) return $this->prop_meta;
        return isset($this->prop_meta[$prop]) ? $this->prop_meta[$prop] : false;
    }

    public function getFieldOfProp($prop = null) {
        if ($prop === null) return $this->prop_to_field;
        return isset($this->prop_to_field[$prop]) ? $this->prop_to_field[$prop] : false;
    }

    public function getPropOfField($field = null) {
        if ($field === null) return $this->field_to_prop;
        return isset($this->field_to_prop[$field]) ? $this->field_to_prop[$field] : false;
    }

    static public function factory($class) {
        if (!isset(self::$instance[$class]))
            self::$instance[$class] = new self($class);
        return self::$instance[$class];
    }
}

/**
 * 模型实例注册表
 * 没有解决的问题是，如果对存储服务直接使用条件删除方式
 * 对应的模型实例依然不会被清除
 * 只有调用模型的destroy()方法才行
 * 在某些情况下会出现一致性问题
 *
 * @package DataMapper
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Registry {
    /**
     * 是否使用注册表
     */
    static public $enabled = true;

    /**
     * 注册模型实例
     *
     * @param mixed $obj
     * @static
     * @access public
     * @return boolean
     */
    static public function set(Data $obj) {
        if (!self::$enabled) return true;

        $id = $obj->id();
        if (!$id) return false;

        $class = get_class($obj);
        $key = $class . $id;
        listen_event($obj, Data::AFTER_DELETE_EVENT, function() use ($class, $id) {
            Registry::remove($class, $id);
        });

        return Utils\Registry::set($key, $obj);
    }

    /**
     * 根据主键值查找实例
     *
     * @param string $class
     * @param mixed $id
     * @static
     * @access public
     * @return mixed
     */
    static public function get($class, $id) {
        if (!self::$enabled) return false;

        $key = $class . $id;
        return Utils\Registry::get($key);
    }

    /**
     * 从注册表中删除模型实例
     *
     * @param string $class
     * @param mixed $id
     * @static
     * @access public
     * @return void
     */
    static public function remove($class, $id) {
        $key = $class . $id;
        return Utils\Registry::remove($key);
    }
}
