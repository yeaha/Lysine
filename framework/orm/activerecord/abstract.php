<?php
namespace Lysine\ORM;

use Lysine\IStorage;
use Lysine\Utils\Events;
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
abstract class ActiveRecord implements IActiveRecord {
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
    protected $row = array();

    /**
     * 被修改过的数据的键名
     *
     * @var array
     * @access protected
     */
    protected $dirty_row = array();

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
     * 保存当前实例
     *
     * @param boolean $refersh 保存成功后从存储服务重新获取数据
     * @abstract
     * @access public
     * @return Lysine\ORM\ActiveRecord
     */
    abstract public function save($refersh = true);

    /**
     * 删除当前实例
     *
     * @abstract
     * @access public
     * @return boolean
     */
    abstract public function destroy();

    /**
     * 重新获取数据刷新当前实例
     *
     * @abstract
     * @access public
     * @return Lysine\ORM\ActiveRecord
     */
    abstract public function refresh();

    /**
     * 构造函数
     *
     * @param array $row
     * @param boolean $from_storage
     * @access public
     * @return void
     */
    public function __construct(array $row = array(), $from_storage = false) {
        $events = Events::instance();
        $events->addEvent($this, 'before init', array($this, '__before_init'));
        $events->addEvent($this, 'after init', array($this, '__after_init'));

        $events->addEvent($this, 'before save', array($this, '__before_save'));
        $events->addEvent($this, 'after save', array($this, '__after_save'));

        $events->addEvent($this, 'before insert', array($this, '__before_insert'));
        $events->addEvent($this, 'after insert', array($this, '__after_insert'));

        $events->addEvent($this, 'before update', array($this, '__before_update'));
        $events->addEvent($this, 'after update', array($this, '__after_update'));

        $events->addEvent($this, 'before destroy', array($this, '__before_destroy'));
        $events->addEvent($this, 'after destroy', array($this, '__after_destroy'));

        $events->addEvent($this, 'before refresh', array($this, '__before_refresh'));
        $events->addEvent($this, 'after refresh', array($this, '__after_refresh'));

        $this->fireEvent('before init');

        if ($row) $this->row = $row;
        if (!$from_storage) $this->dirty_row = array_keys($row);

        $this->fireEvent('after init');
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
     * 设置指定字段值
     *
     * @param string $col
     * @param mixed $val
     * @param boolean $direct
     * @access public
     * @return Lysine\ORM\ActiveRecrod
     */
    public function set($col, $val = null, $direct = false) {
        if (is_array($col)) {
            $direct = (boolean)$val;
        } else {
            $col = array($col => $val);
        }

        $pk = static::$primary_key;
        foreach ($col as $key => $val) {
            if ($key == $pk && isset($this->row[$pk]) && $this->row[$pk])
                throw new \LogicException(__CLASS__ .': primary key refuse update');

            $this->row[$key] = $val;
            if (!$direct) $this->dirty_row[] = $key;
        }
        if (!$direct) $this->dirty_row = array_unique($this->dirty_row);

        return $this;
    }

    /**
     * 获得指定字段值
     *
     * @param string $col
     * @access public
     * @return mixed
     */
    public function get($col) {
        if (array_key_exists($col, $this->row)) return $this->row[$col];
        return false;
    }

    /**
     * 监听事件
     *
     * @param string $name
     * @param callback $callback
     * @access public
     * @return void
     */
    public function addEvent($name, $callback) {
        Events::instance()->addEvent($this, $name, $callback);
    }

    /**
     * 触发事件
     *
     * @param string $name
     * @access public
     * @return void
     */
    public function fireEvent($name) {
        Events::instance()->fireEvent($this, $name);
    }

    /**
     * 以数组方式返回当前实例的数据
     *
     * @access public
     * @return array
     */
    public function toArray() {
        return $this->row;
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

    public function __before_init() {}
    public function __after_init() {}

    public function __before_save() {}
    public function __after_save() {}

    public function __before_insert() {}
    public function __after_insert() {}

    public function __before_update() {}
    public function __after_update() {}

    public function __before_destroy() {}
    public function __after_destroy() {}

    public function __before_refresh() {}
    public function __after_refresh() {}
}
