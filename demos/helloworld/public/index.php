<?php
require '../config/boot.php';

$resp = app()->run();

$profiler = Lysine\Utils\Profiler::instance();
$profiler->end(true);

$resp->setHeader('X-Use-Time: '. round($profiler->getUseTime('__MAIN__') ?: 0, 6))
     ->sendHeader();
echo $resp;
