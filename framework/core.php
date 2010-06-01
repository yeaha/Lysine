<?php
defined('LY_PATH') or define('LY_PATH', dirname(__FILE__));

defined('LY_DEBUG') or define('LY_DEBUG', false);

include_once LY_PATH .'/base/function.php';

class Lysine {
    static public $class_files = array();

    static public function autoload($class_name) {
        if (class_exists($class_name, false) || interface_exists($class_name, false)) return true;

        if (!self::$class_files) self::$class_files = require LY_PATH .'/class_files.php';

        if (!array_key_exists($class_name, self::$class_files)) return false;

        $file = LY_PATH .'/'. self::$class_files[$class_name];
        if (!file_exists($file) || !is_readable($file)) return false;

        require $file;
        return true;
    }
}

spl_autoload_register(array('Lysine', 'autoload'));
