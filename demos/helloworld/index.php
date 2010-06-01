<?php
require_once dirname(__FILE__) .'/../../framework/core.php';

$urls = array(
    '#^/hello/(.+)#' => 'Controller_Hello',
    '#^/(.*)#' => 'Controller_Index',
);

$include_path = array('.', './model');
$response = Ly_Application::run($urls, $include_path);
echo $response;
