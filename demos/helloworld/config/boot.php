<?php
define('ROOT_DIR', realpath(__DIR__ .'/../'));

require_once __DIR__ .'/../../../framework/core.php';

Lysine\Utils\Profiler::instance()->start('__MAIN__');
Lysine\Config::import(require_once ROOT_DIR .'/config/_config.php');

app()->includePath(ROOT_DIR .'/app');

set_exception_handler(function($exception) {
    $code = \Lysine\__on_exception($exception);
    require_once ROOT_DIR .'/public/_error/500.php';
    die(1);
});

set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
    throw new \Lysine\Error($errstr, $errno, null, array(
        'file' => $errfile,
        'line' => $errline,
    ));
});
