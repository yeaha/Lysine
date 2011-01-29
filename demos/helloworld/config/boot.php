<?php
define('ROOT_DIR', realpath(__DIR__ .'/../'));

require __DIR__ .'/../../../framework/core.php';

Lysine\Utils\Profiler::instance()->start('__MAIN__');
Lysine\Config::import(require ROOT_DIR .'/config/_config.php');

app()->includePath(ROOT_DIR .'/app');

set_exception_handler(function($exception) {
    if (PHP_SAPI == 'cli') {  // run in shell
        echo $exception;
    } else {
        list($code, $header) = \Lysine\__on_exception($exception, false);
        ob_start();
        if (!headers_sent())
            foreach ($header as $h) header($h);
        require ROOT_DIR .'/public/_error/500.php';
        echo ob_get_clean();
    }
    die(1);
});

// 日志配置
use Lysine\Utils\Logging;
Lysine\logger()->setLevel(Logging::INFO)->addHandler(new Logging\FileHandler('lysine_log'));
