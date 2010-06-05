<?php
require_once dirname(__FILE__) .'/../../framework/core.php';

defined('APP_PATH') or define('APP_PATH', dirname(__FILE__));

$urls = array(
    '#^/hello/(.+)#' => 'Controller_Hello',
    '#^/hi/(.+)#' => 'Controller_Hi',
    '#^/(.*)#' => 'Controller_Index',
);

$config = array(
    'db' => array(
        'adapter'   => 'pgsql',
        'host'      => 'localhost',
        'port'      => 5432,
        'user'      => 'dev',
        'pass'      => 'abc'
    ),
);

$include_path = array('.', './model');
$response = app()->setConfig($config)->includePath($include_path)->run($urls);
echo $response;
