<?php
namespace Lysine;
const DIR = __DIR__;

class Config {
    static protected $config = array();

    static public function import(array $config) {
        self::$config = array_merge(self::$config, $config);
    }

    static public function set() {
        $args = func_get_args();
        array_unshift(self::$config);
        return call_user_func_array('\Lysine\array_set', $args);
    }

    static public function get() {
        $path = func_get_args();
        return $path ? array_get(self::$config, $path) : self::$config;
    }
}

function autoload($class) {
    static $files = null;
    if ($files === null) $files = require \Lysine\DIR . '/class_files.php';

    if (substr($class, 0, 1) == '\\') $class = ltrim($class, '\\');

    if (!array_key_exists($class, $files)) return false;
    $file = \Lysine\DIR .'/'. $files[$class];
    if (!is_readable($file)) return false;

    include $file;
    return class_exists($class, false) || interface_exists($class, false);
}
spl_autoload_register('Lysine\autoload');

require \Lysine\DIR .'/base/functions.php';
