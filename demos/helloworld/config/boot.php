<?php
require_once __DIR__ .'/../../../framework/core.php';

define('ROOT_DIR', realpath(__DIR__ .'/../'));

$config = array(
    'app' => array(
        'router' => array(
            'rewrite' => array(
                '#^/(.*)#' => '\Controller\index',
            ),
        ),
        'view' => array(
            'view_dir' => ROOT_DIR .'/app/view',
        ),
    ),
);

Lysine\Config::import($config);

require_once ROOT_DIR .'/lib/functions.php';
set_exception_handler('__on_exception');

app()->includePath(ROOT_DIR .'/app');
