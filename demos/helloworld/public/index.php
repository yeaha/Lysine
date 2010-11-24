<?php
require_once '../config/boot.php';

$resp = app()->run();

$profiler = Lysine\Utils\Profiler::instance();
$profiler->end(true);

$resp->setHeader('X-Use-Time: '. $profiler->getUseTime('__MAIN__') ?: 0)
     ->sendHeader();
echo $resp;
