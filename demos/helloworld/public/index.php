<?php
require __DIR__ .'/../config/boot.php';

set_exception_handler(function($exception) {
    if (PHP_SAPI == 'cli')   // run in shell
        die( (string)$exception );

    list($code, $header) = \Lysine\__on_exception($exception, false);

    ob_start();
    if (!headers_sent())
        foreach ($header as $h) header($h);
    require ROOT_DIR .'/view/_error/500.php';
    echo ob_get_clean();

    die(1);
});

try {
    $resp = app()->run();
} catch (Lysine\HttpError $ex) {
    if (req()->isAjax()) throw $ex;
    $code = $ex->getCode();

    if ($code == 404) {
        $resp = resp()->reset()->setCode($code)->setBody( render_view('_error/404', array('exception' => $ex)) );
    } else {
        throw $ex;
    }
}

$profiler = Lysine\Utils\Profiler::instance();
$profiler->end(true);

$resp->setHeader('X-Runtime: '. round($profiler->getRuntime('__MAIN__') ?: 0, 6))
     ->sendHeader();
echo $resp;
