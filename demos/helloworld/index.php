<?php
require_once __DIR__ .'/../../framework/core.php';
use Lysine as ly;

$urls = array(
    '#/(.*)#' => '\Controller\index',
);

$app = ly\app()->includePath(__DIR__);

$resp = $app->run($urls);
echo $resp;
