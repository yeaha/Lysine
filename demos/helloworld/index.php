<?php
require_once __DIR__ .'/../../framework/core.php';

$config = array(
    'app' => array(
        'router' => array(
            'base_namespace' => 'Controller',
            'map' => array(
                '#^/(.*)#' => '\Controller\index',
            ),
        ),
    ),
);

$app = Lysine\app()->includePath(__DIR__);

$resp = $app->setConfig($config)->run();
echo $resp;
