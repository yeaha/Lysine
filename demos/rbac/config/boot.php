<?php
define('ROOT_DIR', realpath(__DIR__ .'/../'));

require __DIR__ .'/../../../framework/core.php';

Lysine\Utils\Profiler::instance()->start('__MAIN__');
Lysine\Config::import(require ROOT_DIR .'/config/_config.php');

app()->includePath(ROOT_DIR .'/app');

set_exception_handler(function($exception) {
    global $argc;

    if (isset($argc)) {  // run in shell
        echo $exception;
    } else {
        list($code, $header) = \Lysine\__on_exception($exception);
        require ROOT_DIR .'/public/_error/500.php';
    }
    die(1);
});

set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
    $logger = Lysine\logger();
    if (in_array($errno, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
        $logger->error($errstr);
    } elseif (in_array($errno, array(E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING))) {
        $logger->warning($errstr);
    } else {
        $logger->debug($errstr);
    }
});

// 日志配置
use Lysine\Utils\Logging;
Lysine\logger()->setLevel(Logging::INFO)->addHandler(new Logging\FileHandler('lysine_log'));

use Lysine\MVC;
listen_event(app()->getRouter(), MVC\BEFORE_DISPATCH_EVENT, array(Model\Rbac::instance(), 'check'));
