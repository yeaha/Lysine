<?php
namespace Lysine\ORM\DataMapper;

use Lysine\Config;
use Lysine\Storage\Pool;
use Lysine\Storage\Cache;

/**
 * 领域模型元数据
 *
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Meta {
    /**
     * 缓存连接实例
     */
    static private $cache;

    /**
     * 类定义元数据有效的key
     */
    static private $class_meta_keys = array(
        'storage',          // 存储服务配置，一般是设置为存储服务名
        'collection',       // 存储集合名字
        'readonly',         // 只读模型
    );

    /**
     * 默认属性元数据定义数组
     */
    static private $default_prop_meta = array(
        'name' => NULL,             // 属性名
        'field' => NULL,            // 存储字段名
        'type' => NULL,             // 数据类型
        'primary_key' => FALSE,     // 是否主键
        'readonly' => FALSE,        // 是否只读属性
        'internal' => FALSE,        // 是否model自己的属性 和存储数据无直接映射关系
        'getter' => NULL,           // 自定义getter
        'setter' => NULL,           // 自定义setter
    );

    /**
     * 已经解析好的meta集合
     */
    static private $meta_set = array();

    /**
     * meta实例集合
     */
    static private $instance = array();

    /**
     * 领域模型的类名称
     *
     * @var string
     * @access private
     */
    private $class;

    /**
     * 是否只读模型
     *
     * @var boolean
     * @access private
     */
    private $readonly = false;

    /**
     * 存储服务配置
     *
     * @var string
     * @access private
     */
    private $storage;

    /**
     * 存储集合名字
     *
     * @var string
     * @access private
     */
    private $collection;

    /**
     * 主键在存储服务中的名字
     *
     * @var string
     * @access private
     */
    private $primary_key;

    /**
     * 属性元数据定义
     *
     * @var array
     * @access private
     */
    private $props;

    /**
     * 属性名对应的字段名
     *
     * @var array
     * @access private
     */
    private $prop_to_field;

    /**
     * 字段名对应的属性名
     *
     * @var array
     * @access private
     */
    private $field_to_prop;

    /**
     * 构造函数
     *
     * @param string $class
     * @access private
     * @return void
     */
    private function __construct($class) {
        $this->class = $class;

        $meta = null;
        if (isset(self::$meta_set[$class]))
            $meta = self::$meta_set[$class];

        if (!$meta && $cache = self::getCache()) {
            $meta = $cache->get('orm.datamapper.meta.'. $class);
            if ($meta) self::$meta_set[$class] = $meta;
        }

        if (!$meta) {
            $meta = MetaInspector::parse($class);
            if ($meta) self::$meta_set[$class] = $meta;
            if ($meta && $cache) $cache->set('orm.datamapper.meta.'. $class, $meta);
        }

        foreach ($meta as $key => $val) $this->$key = $val;

        foreach ($this->props as $name => $config) {
            if ($config['internal']) continue;

            $field = $config['field'];
            if ($config['primary_key']) $this->primary_key = $field;

            $this->prop_to_field[$name] = $field;
            $this->field_to_prop[$field] = $name;
        }
    }

    /**
     * 获得只读状态
     *
     * @access public
     * @return boolean
     */
    public function getReadonly() {
        return $this->readonly;
    }

    /**
     * 获得存储服务配置信息
     *
     * @access public
     * @return string
     */
    public function getStorage() {
        return $this->storage;
    }

    /**
     * 获得存储集合名字
     *
     * @access public
     * @return string
     */
    public function getCollection() {
        if (!$this->collection)
            throw new \UnexpectedValueException("Undefined {$this->class} collection");
        return $this->collection;
    }

    /**
     * 获得主键在存储服务中的名字
     *
     * @access public
     * @return string
     */
    public function getPrimaryKey() {
        if (!$this->primary_key)
            throw new \UnexpectedValueException("Undefined {$this->class} primary key");
        return $this->primary_key;
    }

    /**
     * 获得指定属性元数据定义
     *
     * @param string $prop
     * @access public
     * @return array
     */
    public function getPropMeta($prop = null) {
        if ($prop === null) return $this->props;

        if (!isset($this->props[$prop]))
            throw new \InvalidArgumentException("Undefined {$this->class} property [{$prop}] meta");
        return $this->props[$prop];
    }

    /**
     * 获得属性对应的字段
     *
     * @access public
     * @return mixed
     */
    public function getFieldOfProp($prop = null) {
        if ($prop === null) return $this->prop_to_field;

        return isset($this->prop_to_field[$prop])
             ? $this->prop_to_field[$prop]
             : false;
    }

    /**
     * 获得字段对应的属性
     *
     * @access public
     * @return mixed
     */
    public function getPropOfField($field = null) {
        if ($field === null) return $this->field_to_prop;

        return isset($this->field_to_prop[$field])
             ? $this->field_to_prop[$field]
             : false;
    }

    /**
     * 是否具有属性
     *
     * @param string $prop
     * @access public
     * @return boolean
     */
    public function haveProperty($prop) {
        return isset($this->props[$prop]);
    }

    /**
     * 格式化并清理获得的元数据
     *
     * @param array $meta
     * @static
     * @access public
     * @return array
     */
    static public function sanitize(array $meta) {
        $class_keys = self::$class_meta_keys;
        foreach ($meta as $key => $val) {
            if ($key == 'props') continue;
            if (!in_array($key, $class_keys)) unset($meta[$key]);
        }

        if (!isset($meta['props'])) $meta['props'] = array();

        $props = array();
        $default = self::$default_prop_meta;
        $prop_keys = array_keys($default);
        foreach ($meta['props'] as $config) {
            if (!isset($config['field']) && !isset($config['internal']))
                $config['field'] = $config['name'];

            if (isset($config['var'])) {
                if (!isset($config['type'])) $config['type'] = $config['var'];
                unset($config['var']);
            }

            foreach ($config as $key => $val)
                if (!in_array($key, $prop_keys)) unset($config[$key]);

            $props[$config['name']] = array_merge($default, $config);
        }
        $meta['props'] = $props;

        return $meta;
    }

    /**
     * 设置缓存服务实例
     *
     * @param Cache $cache
     * @static
     * @access public
     * @return void
     */
    static public function setCache(Cache $cache) {
        self::$cache = $cache;
    }

    /**
     * 获得当前可以使用的缓存服务实例
     *
     * @static
     * @access public
     * @return Cache
     */
    static public function getCache() {
        if (self::$cache === null) {
            if ($config = Config::get('orm', 'datamapper', 'meta', '__cache')) {
                self::$cache = Pool::instance()->get($config);
            } else {
                self::$cache = false;
            }
        }
        return self::$cache;
    }

    /**
     * 直接导入已经配置好的元数据
     *
     * @param array $meta_set
     * @static
     * @access public
     * @return void
     */
    static public function import(array $meta_set) {
        foreach ($meta_set as $class => $meta)
            self::$meta_set[$class] = self::sanitize($meta);
    }

    /**
     * 根据指定的领域模型类名字获得meta实例
     *
     * @param string $class
     * @static
     * @access public
     * @return Meta
     */
    static public function factory($class) {
        if (is_object($class)) $class = get_class($class);

        if (!isset(self::$instance[$class])) self::$instance[$class] = new self($class);
        return self::$instance[$class];
    }
}

/**
 * 从领域模型定义的注释信息中解析元数据
 *
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class MetaInspector {
    /**
     * 从指定的类中解析出类和属性的注释信息
     * 解析的结果需要通过Meta::sanitize()处理后才能使用
     *
     * @param string $class_name
     * @static
     * @access public
     * @return array
     */
    static public function parse($class_name) {
        $class = new \ReflectionClass($class_name);

        $class_comment = $class->getDocComment();
        if ($class_comment === false)
            throw new \UnexpectedValueException('Undefined class ['. $class->getName() .'] meta comment');

        $meta = self::parseComment($class_comment);

        $prop_meta = array();
        foreach ($class->getProperties() as $prop) {
            // DataMapper映射的属性不应该是静态或者公共属性
            // 或者不是当前类所声明的
            if ($prop->isStatic() || $prop->isPublic() || $prop->getDeclaringClass() != $class) continue;

            $prop_comment = $prop->getDocComment();
            if ($prop_comment === false)
                throw new \UnexpectedValueException('Undefined class ['. $class->getName() .'] property ['. $prop->getName() .'] meta comment');

            $result = array_merge(
                self::parseComment($prop_comment),
                array('name' => $prop->getName())
            );

            $prop_meta[] = $result;
        }
        $meta['props'] = $prop_meta;

        $meta = Meta::sanitize($meta);

        if ($parent_class = get_parent_class($class_name)) {
            $parent_meta = Meta::factory($parent_class);
            $meta['props'] = array_merge($parent_meta->getPropMeta(), $meta['props']);
        }

        return $meta;
    }

    /**
     * 解析注释信息
     *
     * @param mixed $comment
     * @static
     * @access private
     * @return void
     */
    static private function parseComment($comment) {
        $comment = preg_split('/[\r\n]+/', $comment);

        $result = array();
        foreach ($comment as $line) {
            $line = trim($line, "/* \t");
            if (!$line) continue;

            if (preg_match('/^@(\w+)\s*(\S+)?/', $line, $match)) {
                $key = strtolower($match[1]);
                $result[$key] = isset($match[2]) ? $match[2] : true;
            }
        }

        return $result;
    }
}
