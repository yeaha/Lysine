<?php
namespace Lysine\ORM;

use Lysine\ORM;
use Lysine\ORM\Registry;
use Lysine\IStorage;
use Lysine\Storage\Pool;

/**
 * ActiveRecord接口
 *
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
interface IActiveRecord {
    /**
     * 根据主键获得实例
     *
     * @param mixed $key
     * @param IStorage $storage
     * @static
     * @access public
     * @return Lysine\ORM\ActiveRecord
     */
    static public function find($key, IStorage $storage = null);
}

/**
 * 数据和业务模型映射封装
 *
 * @uses IActiveRecord
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class ActiveRecord extends ORM implements IActiveRecord {
    /**
     * 存储服务配置
     * @see Lysine\Storage\Pool
     */
    static protected $storage_config;

    /**
     * 存储集合名字
     * 相当于数据库表名字
     */
    static protected $collection;

    /**
     * 主键名
     */
    static protected $primary_key;

    /**
     * 虚拟属性配置
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
     * 关联数据配置
     */
    static protected $referer_config = array();

    /**
     * 只读模型
     */
    static protected $readonly = false;

    /**
     * 是否新建
     *
     * @var boolean
     * @access protected
     */
    protected $fresh = true;

    /**
     * 存储服务连接实例
     *
     * @var Lysine\IStorage
     * @access protected
     */
    protected $storage;

    /**
     * 从存储服务中获得的数据
     *
     * @var array
     * @access protected
     */
    protected $record = array();

    /**
     * 被修改过的数据的键名
     *
     * @var array
     * @access protected
     */
    protected $dirty_record = array();

    /**
     * 缓存的虚拟属性值
     *
     * @var array
     * @access protected
     */
    protected $props = array();

    /**
     * 缓存的关联数据值
     *
     * @var array
     * @access protected
     */
    protected $referer = array();

    /**
     * 获得关联数据
     *
     * @param string $name
     * @abstract
     * @access protected
     * @return mixed
     */
    abstract protected function getReferer($name);

    /**
     * 保存新数据
     *
     * @abstract
     * @access protected
     * @return mixed 新主键值
     */
    abstract protected function insert();

    /**
     * 更新数据
     *
     * @abstract
     * @access protected
     * @return boolean
     */
    abstract protected function update();

    /**
     * 删除当前实例
     *
     * @abstract
     * @access public
     * @return boolean
     */
    abstract protected function delete();

    /**
     * 构造函数
     *
     * @param array $record
     * @param boolean $fresh
     * @access public
     * @return void
     */
    public function __construct(array $record = array(), $fresh = true) {
        $this->fireEvent(ORM::BEFORE_INIT_EVENT);

        if ($record) {
            $this->record = $record;
            $this->fresh = $fresh;

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
     * @param string $fn
     * @param array $args
     * @access public
     * @return mixed
     */
    public function __call($fn, $args) {
        if ($fn == 'getStorage') {  // 获得当前实例的storage
            if (!$this->storage) $this->storage = static::getStorage();
            return $this->storage;
        }
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
        if (static::$readonly)
            throw new \LogicException(get_class($this) .' is readonly!');

        if (isset(static::$props_config[$key]['setter'])) {
            $fn = static::$props_config[$key]['setter'];
            $this->$fn($val);

            unset($this->props[$key]);  // 清除掉getter的结果
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

            $config = static::$props_config[$key]['getter'];

            if (is_array($config)) {
                $fn = array_shift($config);
                $cache = array_shift($config);
            } else {
                $fn = $config;
                $cache = false;
            }

            $prop = $this->$fn();
            if ($cache) $this->props[$key] = $prop;

            return $prop;
        }

        if (isset(static::$referer_config[$key]))
            return $this->getReferer($key);

        return $this->get($key);
    }

    /**
     * 得到当前实例主键值
     *
     * @access public
     * @return mixed
     */
    public function id() {
        return $this->get(static::$primary_key);
    }

    /**
     * 是否新建数据
     *
     * @access public
     * @return boolean
     */
    public function isFresh() {
        return $this->fresh;
    }

    /**
     * 设置指定字段值
     *
     * @param string $field
     * @param mixed $val
     * @param boolean $direct
     * @access public
     * @return Lysine\ORM\ActiveRecrod
     */
    public function set($field, $val = null, $direct = false) {
        if (static::$readonly)
            throw new \LogicException(get_class($this) .' is readonly!');

        if (is_array($field)) {
            $values = $field;
            $direct = (boolean)$val;
        } else {
            $values = array($field => $val);
        }

        if (!$this->fresh) {
            $pk = static::$primary_key;
            if (isset($values[$pk]))
                throw new \LogicException(get_class($this) .': Primary key refuse update');

            if ($fields = array_diff(array_keys($values), array_keys($this->record)))
                throw new \InvalidArgumentException(get_class($this) .': Undefined field ['. implode(',', $fields) .']');
        }

        foreach ($values as $key => $val)
            $this->record[$key] = $val;

        if (!$direct)
            $this->dirty_record = array_unique(
                array_merge($this->dirty_record, array_keys($values))
            );

        return $this;
    }

    /**
     * 获得指定字段值
     *
     * @param string $field
     * @access public
     * @return mixed
     */
    public function get($field) {
        if (array_key_exists($field, $this->record))
            return $this->record[$field];

        if (!$this->fresh)
            throw new \InvalidArgumentException(get_class($this) .': Undefined field ['. $field .']');

        return false;
    }

    /**
     * 保存当前实例
     *
     * @abstract
     * @access public
     * @return Lysine\ORM\ActiveRecord
     */
    public function save() {
        if (static::$readonly)
            throw new \LogicException(get_class($this) .' is readonly!');

        $record = $this->record;
        $pk = static::$primary_key;

        // 没有任何字段被改动过，而且主键值不为空
        // 说明这是从数据库中获得的数据，而且没改过，不需要保存
        if (!$this->dirty_record && isset($record[$pk]) && !$record[$pk]) return $this;

        $this->fireEvent(ORM::BEFORE_SAVE_EVENT);

        if ($this->fresh) {
            $this->fireEvent(ORM::BEFORE_INSERT_EVENT);
            if ($result = $this->insert()) {
                $this->set($pk, $result);
                $this->fireEvent(ORM::AFTER_INSERT_EVENT);
                Registry::set($this);
            }
        } else {
            $this->fireEvent(ORM::BEFORE_UPDATE_EVENT);
            if ($result = $this->update()) $this->fireEvent(ORM::AFTER_UPDATE_EVENT);
        }

        if ($result) {
            $this->fresh = false;
            $this->dirty_record = $this->referer = $this->props = array();
        }

        $this->fireEvent(ORM::AFTER_SAVE_EVENT);
        return $this;
    }

    /**
     * 销毁当前实例
     *
     * @access public
     * @return boolean
     */
    public function destroy() {
        if ($this->fresh) return false;

        if (static::$readonly)
            throw new \LogicException(get_class($this) .' is readonly!');

        $id = $this->id();
        $this->fireEvent(ORM::BEFORE_DELETE_EVENT);
        if (!$this->delete()) return false;

        $this->fireEvent(ORM::AFTER_DELETE_EVENT);

        $this->record = $this->dirty_record = $this->referer = $this->props = array();
        $this->storage = null;

        return true;
    }

    /**
     * 以数组方式返回当前实例的数据
     *
     * @param boolean $only_dirty
     * @access public
     * @return array
     */
    public function toArray($only_dirty = false) {
        if (!$only_dirty) return $this->record;

        $record = array();
        foreach ($this->dirty_record as $field)
            $record = $this->record[$field];
        return $record;
    }

    /**
     * 设置当前实例使用的存储服务连接实例
     *
     * @param IStorage $storage
     * @access public
     * @return Lysine\ORM\ActiveRecord
     */
    public function setStorage(IStorage $storage) {
        $this->storage = $storage;
        return $this;
    }

    /**
     * 获得当前类的存储服务连接实例
     *
     * @static
     * @access public
     * @return Lysine\IStorage
     */
    static public function getStorage() {
        return Pool::instance()->get(static::$storage_config);
    }
}
