<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM\DataMapper\Meta;
use Lysine\ORM\DataMapper\DBMapper;
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
    /**
     * 是否新建数据
     *
     * @var boolean
     * @access protected
     * @internal
     */
    protected $is_fresh = true;

    /**
     * 被修改过的属性
     *
     * @var array
     * @access protected
     * @internal
     */
    protected $dirty_props = array();

    /**
     * 读取属性
     *
     * @param string $prop
     * @access public
     * @return mixed
     */
    public function __get($prop) {
        // 修改过的属性值会覆盖原来的属性值
        if (array_key_exists($prop, $this->dirty_props))
            return $this->dirty_props[$prop];

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
        try {
            $prop_meta = static::getMeta()->getPropMeta($prop);
        } catch (\InvalidArgumentException $ex) {
            throw new \InvalidArgumentException(get_class($this) .': Undefined property ['. $prop .']', 0, $ex);
        }

        if ($prop_meta['readonly'])
            throw new \LogicException(get_class($this) .': Property ['. $prop .'] is readonly');

        if (!$this->is_fresh && $prop_meta['primary_key'] && $this->$prop)
            throw new \LogicException(get_class($this) .': Property ['. $prop .'] refuse update');

        $this->dirty_props[$prop] = $val;
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
        $prop = $meta->getFieldToProp($meta->getPrimaryKey());
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
     * 以数组方式返回模型属性数据
     * 只包含字段对应的属性
     *
     * @param boolean $only_dirty 只返回修改过的属性
     * @access public
     * @return array
     */
    public function toArray($only_dirty = false) {
        if ($only_dirty) return $this->dirty_props;

        $props = array();
        foreach (array_keys(static::getMeta()->getPropMeta()) as $prop)
            $props[$prop] = $this->$prop;

        return array_merge($props, $this->dirty_props);
    }

    /**
     * 保存到存储服务中
     *
     * @access public
     * @return Lysine\ORM\DataMapper\Data;
     */
    public function save() {
        $mapper = static::getMapper();
        return $this->is_fresh ? $mapper->create($this) : $mapper->update($this);
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
        return Meta::factory(get_called_class());
    }
}

/**
 * 使用数据库存储方式的领域模型
 *
 * @uses Data
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class DBData extends Data {
    /**
     * 获得数据映射关系封装
     *
     * @static
     * @access public
     * @return void
     */
    static public function getMapper() {
        return DBMapper::factory(get_called_class());
    }

    /**
     * 发起数据库查询
     *
     * @static
     * @access public
     * @return Lysine\Storage\DB\Select
     */
    static public function select() {
        return static::getMapper()->select();
    }
}
