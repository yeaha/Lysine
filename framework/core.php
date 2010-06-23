<?php
namespace Lysine;
const PATH = __DIR__;

function autoload($class) {
    static $files = null;
    if ($files === null) $files = require \Lysine\PATH . '/class_files.php';

    if (!array_key_exists($class, $files)) return false;
    $file = \Lysine\PATH .'/'. $files[$class];
    if (!is_readable($file)) return false;

    include $file;
    return class_exists($class, false) || interface_exists($class, false);
}

spl_autoload_register('Lysine\autoload');

require \Lysine\PATH .'/base/functions.php';
