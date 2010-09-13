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

    const BEFORE_PUT_EVENT = 'before put';
    const AFTER_PUT_EVENT = 'after put';

    const BEFORE_REPLACE_EVENT = 'before replace';
    const AFTER_REPLACE_EVENT = 'after replace';

    const BEFORE_DELETE_EVENT = 'before delete';
    const AFTER_DELETE_EVENT = 'after delete';
    // }}}

    // {{{ 内置事件响应方法
    public function __before_init() {}
    public function __after_init() {}

    public function __before_save() {}
    public function __after_save() {}

    public function __before_put() {}
    public function __after_put() {}

    public function __before_replace() {}
    public function __after_replace() {}

    public function __before_delete() {}
    public function __after_delete() {}
    // }}}
}
