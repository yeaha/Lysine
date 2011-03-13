<?php
namespace Lysine\ORM\DataMapper;

use Lysine\Config;
use Lysine\Storage\Pool;
use Lysine\Storage\Cache;
use Lysine\OrmError;

/**
 * 领域模型元数据
 *
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Meta {
    /**
     * 默认属性元数据定义数组
     */
    static private $default_prop_meta = array(
        'name' => NULL,             // 属性名
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
            $prop_meta['field'] = $prop_meta['field'] ?: $prop_meta['name'];
            $this->prop_to_field[$prop_name] = $prop_meta['field'];
        }
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

    public function getPrimaryKey($as_field = false) {
        if (!$primary_key = $this->primary_key)
            throw OrmError::undefined_primarykey($this->class);

        if ($as_field) return $this->getFieldOfProp($primary_key);
        return $primary_key;
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
        if ($filed === null) return $this->field_to_prop;
        return isset($this->field_to_prop[$field]) ? $this->field_to_prop[$field] : false;
    }

    static public function factory($class) {
        if (!isset(self::$instance[$class]))
            self::$instance[$class] = new self($class);
        return self::$instance[$class];
    }
}
