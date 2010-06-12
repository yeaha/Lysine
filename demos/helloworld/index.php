<?php
require_once dirname(__FILE__) .'/../../framework/core.php';

defined('APP_PATH') or define('APP_PATH', dirname(__FILE__));

$urls = array(
    '#^/hello/(.+)#' => 'Controller_Hello',
    '#^/hi/(.+)#' => 'Controller_Hi',
    '#^/redirect/lysine#' => 'Controller_Redirect',
    '#^/test#' => 'Controller_Test',
    '#^/(.*)#' => 'Controller_Index',
);

$config = array(
    'db' => array(
        'dsn' => array(
            '__default__' => array(
                'dsn'       => 'pgsql:host=127.0.0.1 dbname=lysine.test',
                'user'      => 'dev',
                'pass'      => 'abc',
            ),
        ),
    ),
);

$class_map = require 'class_files.php';
$include_path = array('.', './model');
$response = app()->setConfig($config)
                 ->includeClassMap($class_map)
                 ->includePath($include_path)
                 ->run($urls);
echo $response;
