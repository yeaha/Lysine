<?php
require_once '../config/boot.php';

$resp = app()->run();

$resp->sendHeader();
echo $resp;
