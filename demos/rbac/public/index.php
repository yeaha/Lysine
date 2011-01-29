<?php
require __DIR__ .'/../config/boot.php';

$resp = app()->run();

$profiler = Lysine\Utils\Profiler::instance();
$profiler->end(true);

$resp->setHeader('X-Runtime: '. round($profiler->getRuntime('__MAIN__') ?: 0, 6))
     ->sendHeader();
echo $resp;
