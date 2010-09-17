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
}
