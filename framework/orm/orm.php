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
    public function __before_init() {}
    public function __after_init() {}

    public function __before_save() {}
    public function __after_save() {}

    public function __before_insert() {}
    public function __after_insert() {}

    public function __before_update() {}
    public function __after_update() {}

    public function __before_delete() {}
    public function __after_delete() {}
    // }}}

    abstract public function id();
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
        $obj->addEvent(ORM::AFTER_DELETE_EVENT, function() use ($class, $id) {
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
