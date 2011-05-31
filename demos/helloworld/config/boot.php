<?php
define('ROOT_DIR', realpath(__DIR__ .'/../'));
define('DEBUG', true);

require __DIR__ .'/../../../framework/core.php';

Lysine\Utils\Profiler::instance()->start('__MAIN__');
Lysine\Config::import(require ROOT_DIR .'/config/_config.php');

app()->includePath(ROOT_DIR);

// 日志配置
use Lysine\Utils\Logging;
Lysine\logger()
    ->setLevel(DEBUG ? Logging::DEBUG : Logging::ERROR)
    ->addHandler(new Logging\FileHandler('sys_log'));
