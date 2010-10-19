<?php
namespace Lysine;

/**
 * ORM基类
 *
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class ORM {
    // {{{ 内置事件
    const BEFORE_INIT_EVENT = 'before init';
    const AFTER_INIT_EVENT = 'after init';

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
        self::BEFORE_INIT_EVENT => '__before_init',
        self::AFTER_INIT_EVENT => '__after_init',
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

    abstract public function id();

    /**
     * 触发事件
     *
     * @param string $event
     * @param mixed $args
     * @access public
     * @return integer
     */
    public function fireEvent($event, $args = null) {
        if (isset(self::$event_methods[$event])) {
            $method = self::$event_methods[$event];
            $this->$method();
        }

        $args = is_array($args) ? $args : array_slice(func_get_args(), 1);
        array_unshift($args, $this);
        return fire_event($this, $event, $args);
    }
}

namespace Lysine\ORM;

use Lysine\ORM;
use Lysine\Utils;

/**
 * ORM模型实例注册表
 * 没有解决的问题是，如果对存储服务直接使用条件删除方式
 * 对应的模型实例依然不会被清除
 * 只有调用模型的destroy()方法才行
 * 在某些情况下会出现一致性问题
 *
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Registry {
    /**
     * 是否使用调查表
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
    static public function set(ORM $obj) {
        if (!self::$enabled) return true;

        $id = $obj->id();
        if (!$id) return false;

        $class = get_class($obj);
        $key = $class . $id;
        listen_event($obj, ORM::AFTER_DELETE_EVENT, function() use ($class, $id) {
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
