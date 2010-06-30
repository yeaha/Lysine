<?php
require_once __DIR__ .'/../../framework/core.php';
use Lysine as ly;

$config = array(
    'app' => array(
        'router' => array(
            '#/(.*)#' => '\Controller\index',
        ),
    ),
);

$app = ly\app()->includePath(__DIR__);

$resp = $app->setConfig($config)->run();
echo $resp;
