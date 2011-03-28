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
 */
abstract class Data extends ORM implements IData {
    static protected $storage;
    static protected $collection;
    static protected $readonly = false;
    static protected $props_meta = array();

    private $is_fresh = true;
    private $props = array();
    private $dirty_props = array();

    public function __construct(array $props = null) {
        if ($props) $this->setProp($props);
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

    public function getProp($prop) {
        if (!$prop_meta = static::getMeta()->getPropMeta($prop))
            throw Error::undefined_property(get_class($this), $prop);

        $val = isset($this->props[$prop]) ? $this->props[$prop] : null;
        if ($val === null && $prop_meta['default'] !== null)
            return $prop_meta['default'];
        return $val;
    }

    public function setProp($prop, $val = null, $static = true) {
        if (static::$readonly) throw ORM::readonly($this);

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
        if (static::$readonly) throw ORM::readonly($this);
        return static::getMapper()->save($this);
    }

    public function destroy() {
        if (static::$readonly) throw ORM::readonly($this);
        return static::getMapper()->destroy($this);
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
