<?php
namespace Lysine;

abstract class ORM {
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
}
