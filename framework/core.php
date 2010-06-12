<?php
defined('LY_PATH') or define('LY_PATH', dirname(__FILE__));

defined('LY_DEBUG') or define('LY_DEBUG', false);

include_once LY_PATH .'/base/function.php';

class Lysine {
    static public $class_files = array();

    static public function autoload($class) {
        if (!self::$class_files) self::$class_files = require LY_PATH .'/class_files.php';

        if (!array_key_exists($class, self::$class_files)) return false;
        $file = LY_PATH .'/'. self::$class_files[$class];
        if (!is_readable($file)) return false;

        include($file);
        return class_exists($class, false) || interface_exists($class, false);
    }
}

spl_autoload_register(array('Lysine', 'autoload'));
