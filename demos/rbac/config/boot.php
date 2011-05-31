<?php
define('ROOT_DIR', realpath(__DIR__ .'/../'));
define('DEBUG', true);

require __DIR__ .'/../../../framework/core.php';

Lysine\Utils\Profiler::instance()->start('__MAIN__');
Lysine\Config::import(require ROOT_DIR .'/config/_config.php');

app()->includePath(ROOT_DIR);

// 系统日志，根据需要开启，需要创建ROOT_DIR .'/logs'目录
//use Lysine\Utils\Logging;
//Lysine\logger()
//    ->setLevel(DEBUG ? Logging::DEBUG : Logging::ERROR)
//    ->addHandler(new Logging\FileHandler('sys_log'));

use Lysine\MVC;
listen_event(app()->getRouter(), MVC\BEFORE_DISPATCH_EVENT, array(Model\Rbac::instance(), 'check'));
