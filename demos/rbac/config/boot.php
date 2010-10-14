<?php
require_once __DIR__ .'/../../../framework/core.php';

define('ROOT_DIR', realpath(__DIR__ .'/../'));

$config = array(
    'app' => require_once ROOT_DIR .'/config/_app.php',
    'storage' => require_once ROOT_DIR .'/config/_storage.php',
);

Lysine\Config::import($config);

require_once ROOT_DIR .'/lib/functions.php';
set_exception_handler('__on_exception');

app()->includePath(ROOT_DIR .'/app');

use Lysine\MVC;
listenEvent(app()->getRouter(), MVC\BEFORE_DISPATCH_EVENT, array(Model\Rbac::instance(), 'check'));
