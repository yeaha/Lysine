<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM;
use Lysine\Error;
use Lysine\OrmError;

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
abstract class Data extends ORM implements IData {
    /**
     * 是否新建数据
     *
     * @var boolean
     * @access private
     * @internal true
     */
    private $is_fresh = true;

    /**
     * 是否只读
     *
     * @var boolean
     * @access private
     * @internal true
     */
    private $is_readonly;

    /**
     * 被修改过的属性名
     *
     * @var array
     * @access private
     * @internal true
     */
    private $dirty_props = array();

    /**
     * 构造函数
     * 
     * @param array $props 
     * @access public
     * @return void
     */
    public function __construct(array $props = null) {
        if ($props) $this->setProp($props);
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
     * 读取属性
     *
     * @param string $prop
     * @access public
     * @return mixed
     */
    public function __get($prop) {
        return $this->getProp($prop);
    }

    /**
     * 魔法方法
     * 设置属性
     *
     * @param string $prop
     * @param mixed $val
     * @access public
     * @return void
     */
    public function __set($prop, $val) {
        $this->setProp($prop, $val);
    }

    /**
     * 读取属性
     *
     * @param string $prop
     * @access public
     * @return mixed
     */
    public function getProp($prop) {
        if ($prop_meta = static::getMeta()->getPropMeta($prop)) {
            if ($getter = $prop_meta['getter']) {
                if (!method_exists($this, $getter))
                    throw Error::call_undefined($getter, get_class($this));
                return $this->$getter();
            }
        }

        return $this->$prop;
    }

    /**
     * 设置属性
     *
     * @param mixed $prop
     * @param mixed $val
     * @param boolean $direct
     * @access public
     * @return void
     */
    public function setProp($prop, $val = null, $direct = false) {
        if ($this->isReadonly())
            throw OrmError::readonly($this);

        if (is_array($prop)) {
            $direct = (bool)$val;
            foreach ($prop as $p => $val) $this->setProp($p, $val, $direct);
            return $this;
        }

        $meta = static::getMeta();

        if (!$prop_meta = $meta->getPropMeta($prop))
            throw Error::undefined_property(get_class($this), $prop);

        if (!$this->is_fresh && ($prop_meta['refuse_update'] || $prop_meta['primary_key']))
            throw OrmError::refuse_update($this, $prop);

        if ($setter = $prop_meta['setter']) {
            if (!method_exists($this, $setter))
                throw Error::call_undefined($setter, get_class($this));
            $this->$setter($val, $direct);
        } else {
            $this->changeProp($prop, $val, $direct);
        }

        return $this;
    }

    /**
     * 修改属性值
     * setProp()和changeProp()的区别在于
     * setProp()会调用setter，检查refuse_update等等
     * changeProp()主要是内部使用
     *
     * @param string $prop
     * @param mixed $val
     * @param boolean $direct
     * @access protected
     * @return Data
     */
    protected function changeProp($prop, $val, $direct = false) {
        $this->$prop = $val;
        if ($direct) return $this;

        $prop_meta = static::getMeta()->getPropMeta($prop);
        if (!$prop_meta['internal'] && !in_array($prop, $this->dirty_props))
            $this->dirty_props[] = $prop;
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

        foreach (static::getMeta()->getPropMeta() as $prop => $prop_meta) {
            if ($prop_meta['internal']) continue;
            $props[$prop] = $this->$prop;
        }

        return $props;
    }

    /**
     * 保存当前实例
     *
     * @access public
     * @return mixed
     */
    public function save() {
        return static::getMapper()->save($this);
    }

    /**
     * 销毁当前实例
     *
     * @access public
     * @return boolean
     */
    public function destroy() {
        return static::getMapper()->delete($this);
    }

    /**
     * 根据主键生成实例
     * 不使用参数声明，便于具体的Data方法重载此方法
     * 如果子类的find参数和这里不一致，会抛出E_STRICT错误
     *
     * @static
     * @access public
     * @see \Lysine\ORM\DataMapper\Mapper::find
     * @return void
     */
    static public function find(/* $id */) {
        return call_user_func_array(array(static::getMapper(), 'find'), func_get_args());
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
