<?php
define('ROOT_DIR', realpath(__DIR__ .'/../'));

require_once __DIR__ .'/../../../framework/core.php';

Lysine\Utils\Profiler::instance()->start('__MAIN__');
Lysine\Config::import(require_once ROOT_DIR .'/config/_config.php');

require_once ROOT_DIR .'/lib/functions.php';
set_exception_handler('__on_exception');
set_error_handler('__on_error');

app()->includePath(ROOT_DIR .'/app');
