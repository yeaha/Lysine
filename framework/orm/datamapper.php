<?php
// @README: ORM DataMapper实现

namespace Lysine\DataMapper;

use Lysine\Config,
    Lysine\IStorage,
    Lysine\Storage\Manager,
    Lysine\Utils;

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
    protected function __before_init() {}
    protected function __after_init() {}

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

    // 存储服务
    static protected $storage;
    // 存储集合
    static protected $collection;
    // 只读开关
    static protected $readonly = false;
    // 属性元数据定义，strict默认值
    // @see Lysine\DataMapper\Data->setProp()
    static protected $strict = false;
    // 属性元数据
    // @see Lysine\DataMapper\Meta
    static protected $props_meta = array();

    // 是否新建
    private $is_fresh = true;
    // 属性值
    protected $props = array();
    // 被修改过的属性
    protected $dirty_props = array();

    /**
     * 构造函数
     *
     * @param array $props      属性值
     * @param bool  $is_fresh   是否新建
     */
    public function __construct(array $props = null, $is_fresh = true) {
        $this->__before_init();

        if ($props) $this->setProp($props, $strict = true);

        $this->is_fresh = $is_fresh;
        if (!$is_fresh) $this->dirty_props = array();

        $this->__after_init();
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        clear_event($this);
    }

    /**
     * 把属性填充进Data，并设置为非新建
     *
     * @param array $props 属性值
     * @access public
     * @return Data
     */
    public function __fill(array $props) {
        $this->props = array_merge($this->props, $props);
        $this->is_fresh = false;
        $this->dirty_props = array();
        return $this;
    }

    /**
     * 魔法方法，读属性
     *
     * @param string $prop
     * @access public
     * @return mixed
     */
    public function __get($prop) {
        return $this->getProp($prop);
    }

    /**
     * 魔法方法，写属性
     *
     * @param string    $prop   属性名
     * @param mixed     $val    属性值
     * @access public
     * @return void
     */
    public function __set($prop, $val) {
        $this->setProp($prop, $val, $strict = true);
    }

    /**
     * 是否有指定属性
     *
     * @param string $prop 属性名
     * @access public
     * @return bool
     */
    public function hasProp($prop) {
        return (bool)static::getMeta()->getPropMeta($prop);
    }

    /**
     * 读属性，如果属性未设置则返回默认值或NULL
     *
     * @param string $prop  属性名
     * @access public
     * @return mixed
     */
    public function getProp($prop = null) {
        // 返回指定的属性值
        if ($prop) {
            if (!$prop_meta = static::getMeta()->getPropMeta($prop))
                throw Error::undefined_property(get_class($this), $prop);

            return isset($this->props[$prop])
                 ? $this->props[$prop]
                 : $prop_meta['default'];
        }

        // 返回所有属性值
        $props = array();
        foreach (static::getMeta()->getPropMeta() as $prop => $prop_meta)
            $props[$prop] = isset($this->props[$prop])
                          ? $this->props[$prop]
                          : $prop_meta['default'];
        return $props;
    }

    /**
     * 写属性
     *
     * 元数据strict声明为true的属性，只能被以下3种情况修改
     * $data->prop = $val;
     * $data->setProp('prop', $val, true);
     * $data->setProp(array $props, true);
     *
     * @param string|array  $prop   属性
     * @param mixed         $val    值
     * @param bool          $strict 严格模式，是否抛出异常
     * @access public
     * @return Data
     */
    public function setProp($prop, $val = null, $strict = false) {
        if (static::$readonly) throw Error::readonly($this);

        if (is_array($prop)) {
            $props = $prop;
            $strict = ($val === null) ? false : (bool)$val;
        } else {
            $props = array($prop => $val);
        }

        $meta = static::getMeta();
        foreach ($props as $prop => $val) {
            if (!$prop_meta = $meta->getPropMeta($prop)) {
                if (!$strict) continue;
                throw Error::undefined_property(get_class($this), $prop);
            }

            // 严格模式的属性不允许在非严格模式下修改
            if ($prop_meta['strict'] && !$strict)
                continue;

            if (!$this->is_fresh && ($prop_meta['refuse_update'] || $prop_meta['primary_key'])) {
                if (!$strict) continue;
                throw Error::refuse_update($this, $prop);
            }

            if ($prop_meta['pattern'] && !preg_match($prop_meta['pattern'], $val))
                throw Error::mismatching_pattern($this, $prop, $prop_meta['pattern']);

            $val = $this->formatProp($prop, $val, $prop_meta);

            if (!$prop_meta['allow_null'] && $val === null)
                throw Error::not_allow_null($this, $prop);

            $this->changeProp($prop, $val);
        }

        return $this;
    }

    /**
     * 修改属性
     * setProp()检查值是否符合属性元数据要求
     * changeProp()的重点是根据已有值的情况决定是否修改属性以及修改Data内部状态
     *
     * @param string $prop 属性
     * @param mixed  $val  值
     * @access protected
     * @return bool
     */
    protected function changeProp($prop, $val) {
        if (!isset($this->props[$prop])) {
            if ($val === null) return false;
        } elseif ($val === $this->props[$prop]) {
            return false;
        }

        $this->props[$prop] = $val;
        if (!in_array($prop, $this->dirty_props))
            $this->dirty_props[] = $prop;
        return true;
    }

    /**
     * 修改属性之前，根据属性元数据定义格式化属性值
     * 业务Data可通过重载此方法，对业务数据进行更灵活的预处理
     *
     * @param string    $prop
     * @param mixed     $val
     * @param array     $prop_meta
     * @access protected
     * @return mixed
     */
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

    /**
     * 获得唯一编号，对数据库就是主键
     *
     * @access public
     * @return mixed
     */
    public function id() {
        $prop = static::getMeta()->getPrimaryKey($as_prop = true);
        return $this->getProp($prop);
    }

    /**
     * 设置“新建”状态
     *
     * @access public
     * @return Data
     */
    public function setFresh($fresh) {
        $this->is_fresh = (bool)$fresh;
        return $this;
    }

    /**
     * 是否新建
     *
     * @access public
     * @return bool
     */
    public function isFresh() {
        return $this->is_fresh;
    }

    /**
     * 是否被修改过
     *
     * @access public
     * @return bool
     */
    public function isDirty() {
        return (bool)$this->dirty_props;
    }

    /**
     * 是否只读
     *
     * @access public
     * @return bool
     */
    public function isReadonly() {
        return static::$readonly;
    }

    /**
     * 把所有属性值转换为数组
     *
     * @access public
     * @return array
     */
    public function toArray($only_dirty = false) {
        if (!$only_dirty) return $this->props;

        $props = array();
        foreach ($this->dirty_props as $prop)
            $props[$prop] = $this->props[$prop];
        return $props;
    }

    /**
     * 保存
     * 其实Data不和存储打交道，通过Mapper完成此过程
     *
     * @access public
     * @return bool
     */
    public function save() {
        if (static::$readonly) throw Error::readonly($this);
        return static::getMapper()->save($this);
    }

    /**
     * 删除
     *
     * @access public
     * @return bool
     */
    public function destroy() {
        if (static::$readonly) throw Error::readonly($this);
        return static::getMapper()->destroy($this);
    }

    /**
     * 从存储中重新获得数据刷新所有属性
     *
     * @access public
     * @return Data
     */
    public function refresh() {
        if (!$this->isFresh())
            static::getMapper()->refresh($this);

        return $this;
    }

    /**
     * 触发事件
     *
     * @param string $event 事件名
     * @param array  $args  事件参数
     * @see Lysine\Utils\Event
     * @access public
     * @return integer
     */
    public function fireEvent($event, array $args = null) {
        if (isset(self::$event_methods[$event])) {
            $method = self::$event_methods[$event];
            $this->$method();
        }

        return fire_event($this, $event, $args);
    }

    /**
     * 得到属性元数据定义
     *
     * @access public
     * @static
     * @return array
     */
    static public function getMetaDefine() {
        $meta = array(
            'storage' => static::$storage,
            'collection' => static::$collection,
            'strict' => static::$strict,
            'props' => static::$props_meta,
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

    /**
     * 得到当前Data的Meta对象
     *
     * @access public
     * @static
     * @return Meta
     */
    static public function getMeta() {
        return static::getMapper()->getMeta();
    }

    /**
     * 根据主键得到对应的Data
     *
     * @access public
     * @static
     * @return Data
     */
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
     * 重新从存储服务中查询数据，刷新实例
     *
     * @param Data $data
     * @access public
     * @return Data
     */
    public function refresh(Data $data) {
        if ($record = $this->doFind($data->id()))
            $this->package($record, $data);
    }

    /**
     * 保存模型数据到存储服务
     *
     * @param Data $data
     * @access public
     * @return bool
     */
    public function save(Data $data) {
        if ($data->isReadonly())
            throw Error::readonly($data);

        if (!($is_fresh = $data->isFresh()) && !($is_dirty = $data->isDirty()))
            return true;

        $data->fireEvent(Data::BEFORE_SAVE_EVENT, array($data));
        if ($is_fresh) {
            $data->fireEvent(Data::BEFORE_INSERT_EVENT, array($data));
        } elseif ($is_dirty) {
            $data->fireEvent(Data::BEFORE_UPDATE_EVENT, array($data));
        }

        $props = $data->toArray();
        foreach ($this->getMeta()->getPropMeta() as $prop => $prop_meta) {
            if (!$prop_meta['allow_null'] && !isset($props[$prop]) && $prop_meta['default'] === null)
                throw Error::not_allow_null($data, $prop);
        }

        if ($is_fresh) {
            if ($result = $this->insert($data))
                $data->fireEvent(Data::AFTER_INSERT_EVENT, array($data));
        } elseif ($is_dirty) {
            if ($result = $this->update($data))
                $data->fireEvent(Data::AFTER_UPDATE_EVENT, array($data));
        }

        if ($result)
            $data->fireEvent(Data::AFTER_SAVE_EVENT, array($data));

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
        if (!$id = $this->doInsert($data)) return false;

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
        if (!$this->doUpdate($data)) return false;

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
            throw Error::readonly($data);

        if ($data->isFresh()) return true;

        $data->fireEvent(Data::BEFORE_DELETE_EVENT, array($data));
        if (!$this->doDelete($data)) return false;

        $data->fireEvent(Data::AFTER_DELETE_EVENT, array($data));
        Registry::remove($this->class, $data->id());
        return true;
    }

    /**
     * 把存储服务中获得的数据实例化为领域模型
     *
     * @param array $record
     * @param Data $data
     * @access public
     * @return Lysine\DataMapper\Data
     */
    public function package(array $record, Data $data = null) {
        if (!$data) {
            $data_class = $this->class;
            $data = new $data_class;
        }

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
        'pattern' => NULL,          // 正则表达式检查
        'strict' => NULL,           // 是否采用严格模式，见Data->setProp()
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
            if (!isset($prop_meta['strict'])) $prop_meta['strict'] = $define['strict'];
            if ($prop_meta['primary_key']) $this->primary_key = $prop_meta['field'];

            $this->prop_to_field[$prop_name] = $prop_meta['field'];
        }

        if (!$this->primary_key)
            throw Error::undefined_primarykey($this->class);

        $this->field_to_prop = array_flip($this->prop_to_field);
        $this->prop_meta = $define['props'];
    }

    public function getStorage() {
        return $this->storage;
    }

    public function getCollection() {
        if (!$collection = $this->collection)
            throw Error::undefined_collection($this->class);
        return $collection;
    }

    public function getPrimaryKey($as_prop = false) {
        if (!$primary_key = $this->primary_key)
            throw Error::undefined_primarykey($this->class);

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

class Error extends \Lysine\Error {
    static public function readonly($class) {
        if ($class instanceof Data) $class = get_class($class);
        return new static("{$class} is readonly");
    }

    static public function not_allow_null($class, $prop) {
        if ($class instanceof Data) $class = get_class($class);
        return new static("{$class}: Property {$prop} not allow null");
    }

    static public function refuse_update($class, $prop) {
        if ($class instanceof Data) $class = get_class($class);
        return new static("{$class}: Property {$prop} refuse update");
    }

    static public function undefined_collection($class) {
        if ($class instanceof Data) $class = get_class($class);
        return new static("{$class}: Undefined collection");
    }

    static public function undefined_primarykey($class) {
        if ($class instanceof Data) $class = get_class($class);
        return new static("{$class}: Undefined primary key");
    }

    static public function mismatching_pattern($class, $prop, $pattern) {
        if ($class instanceof Data) $class = get_class($class);
        return new static("{$class}: Property {$prop} mismatching pattern {$pattern}");
    }
}
