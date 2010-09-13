<?php
namespace Lysine\ORM\DataMapper;

use Lysine\Utils\Events;

/**
 * 领域模型接口
 *
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
interface IData {
    /**
     * 获得领域模型数据映射关系封装实例
     *
     * @static
     * @access public
     * @return Lysine\ORM\DataMapper\Mapper
     */
    static public function getMapper();
}

/**
 * 领域模型基类
 *
 * @uses IData
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 * @storage
 * @collection
 */
abstract class Data implements IData {
    const BEFORE_SAVE_EVENT = 'before save';
    const AFTER_SAVE_EVENT = 'after save';

    const BEFORE_PUT_EVENT = 'before put';
    const AFTER_PUT_EVENT = 'after put';

    const BEFORE_REPLACE_EVENT = 'before replace';
    const AFTER_REPLACE_EVENT = 'after replace';

    const BEFORE_DELETE_EVENT = 'before delete';
    const AFTER_DELETE_EVENT = 'after delete';

    /**
     * 是否新建数据
     *
     * @var boolean
     * @access protected
     * @internal
     */
    protected $is_fresh = true;

    /**
     * 被修改过的属性名
     *
     * @var array
     * @access protected
     * @internal
     */
    protected $dirty_props = array();

    /**
     * 是否只读
     *
     * @var boolean
     * @access protected
     * @internal
     */
    protected $is_readonly;

    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $events = Events::instance();

        $events->addEvent($this, self::BEFORE_SAVE_EVENT, array($this, '__before_save'));
        $events->addEvent($this, self::AFTER_SAVE_EVENT, array($this, '__after_save'));

        $events->addEvent($this, self::BEFORE_PUT_EVENT, array($this, '__before_put'));
        $events->addEvent($this, self::AFTER_PUT_EVENT, array($this, '__after_put'));

        $events->addEvent($this, self::BEFORE_REPLACE_EVENT, array($this, '__before_replace'));
        $events->addEvent($this, self::AFTER_REPLACE_EVENT, array($this, '__after_replace'));

        $events->addEvent($this, self::BEFORE_DELETE_EVENT, array($this, '__before_delete'));
        $events->addEvent($this, self::AFTER_DELETE_EVENT, array($this, '__after_delete'));
    }

    /**
     * 析构函数
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        Events::instance()->clearEvent($this);
    }

    /**
     * 魔法方法
     * 读取属性
     *
     * @param string $prop
     * @access public
     * @return mixed
     */
    public function __get($prop) {
        if ($prop_meta = static::getMeta()->getPropMeta($prop)) {
            if ($getter = $prop_meta['getter'])
                return $this->$getter();
        }

        return $this->$prop;
    }

    /**
     * 设置属性
     *
     * @param string $prop
     * @param mixed $val
     * @access public
     * @return void
     */
    public function __set($prop, $val) {
        if ($this->isReadonly())
            throw new \LogicException(get_class($this) .' is readonly');

        $meta = static::getMeta();

        if (!$prop_meta = $meta->getPropMeta($prop))
            throw new \InvalidArgumentException(get_class($this) .': Undefined property ['. $prop .']');

        if ($prop_meta['readonly'])
            throw new \LogicException(get_class($this) .': Property ['. $prop .'] is readonly');

        if (!$this->is_fresh && $prop_meta['primary_key'] && $this->$prop)
            throw new \LogicException(get_class($this) .': Property ['. $prop .'] refuse replace');

        if ($setter = $prop_meta['setter']) {
            $this->$setter($val);
        } else {
            $this->set($prop, $val);
        }
    }

    /**
     * 设置属性
     *
     * @param mixed $prop
     * @param mixed $val
     * @param boolean $direct
     * @access public
     * @return Lysine\ORM\DataMapper\Data
     */
    public function set($prop, $val, $direct = false) {
        if ($this->isReadonly())
            throw new \LogicException(get_class($this) .' is readonly');

        if (is_array($prop)) {
            $props = $prop;
            $direct = (bool)$val;
        } else {
            $props = array($prop => $val);
        }

        foreach ($props as $prop => $val) $this->$prop = $val;
        if (!$direct) $this->dirty_props = array_unique(array_merge($this->dirty_props, array_keys($props)));

        return $this;
    }

    /**
     * 给领域模型填入数据
     *
     * @param array $props
     * @access public
     * @return void
     */
    public function __fill(array $props) {
        $this->is_fresh = false;

        foreach ($props as $prop => $val)
            $this->$prop = $val;

        $this->dirty_props = array();
    }

    /**
     * 返回主键值
     *
     * @access public
     * @return mixed
     */
    public function id() {
        $meta = static::getMeta();
        $prop = $meta->getPropOfField($meta->getPrimaryKey());
        return $this->$prop;
    }

    /**
     * 是否新建
     *
     * @access public
     * @return boolean
     */
    public function isFresh() {
        return $this->is_fresh;
    }

    /**
     * 是否被修改过
     *
     * @access public
     * @return boolean
     */
    public function isDirty() {
        return (bool)$this->dirty_props;
    }

    /**
     * 此模型是否只读
     *
     * @access public
     * @return boolean
     */
    public function isReadonly() {
        if ($this->is_readonly === null)
            $this->is_readonly = static::getMeta()->getReadonly();

        return $this->is_readonly;
    }

    /**
     * 以数组方式返回模型属性数据
     * 只包含字段对应的属性
     *
     * @param boolean $only_dirty 只返回修改过的属性
     * @access public
     * @return array
     */
    public function toArray($only_dirty = false) {
        $props = array();

        if ($only_dirty) {
            foreach ($this->dirty_props as $prop)
                $props[$prop] = $this->$prop;
            return $props;
        }

        foreach (static::getMeta()->getPropMeta() as $prop => $prop_meta)
            $props[$prop] = $this->$prop;

        return $props;
    }

    /**
     * 保存到存储服务中
     *
     * @access public
     * @return Lysine\ORM\DataMapper\Data;
     */
    public function save() {
        return static::getMapper()->save($this);
    }

    /**
     * 从存储服务中删除
     *
     * @access public
     * @return boolean
     */
    public function delete() {
        return static::getMapper()->delete($this);
    }

    /**
     * 监听事件
     *
     * @param string $event
     * @param callable $callback
     * @access public
     * @return void
     */
    public function addEvent($event, $callback) {
        Events::instance()->addEvent($this, $event, $callback);
    }

    /**
     * 触发事件
     *
     * @param string $event
     * @param mixed $args
     * @access public
     * @return void
     */
    public function fireEvent($event, $args = null) {
        if ($args === null) {
            Events::instance()->fireEvent($this, $event);
        } else {
            $args = is_array($args) ? $args : array_slice(func_get_args(), 1);
            Events::instance()->fireEvent($this, $event, $args);
        }
    }

    public function __before_save() {}
    public function __after_save() {}
    public function __before_put() {}
    public function __after_put() {}
    public function __before_replace() {}
    public function __after_replace() {}
    public function __before_delete() {}
    public function __after_delete() {}

    /**
     * 根据主键生成实例
     *
     * @param mixed $key
     * @static
     * @access public
     * @return void
     */
    static public function find($key) {
        return static::getMapper()->find($key);
    }

    /**
     * 获得领域模型元数据封装
     *
     * @static
     * @access public
     * @return Lysine\ORM\DataMapper\Meta
     */
    static public function getMeta() {
        return static::getMapper()->getMeta();
    }
}
