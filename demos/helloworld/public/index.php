<?php
require __DIR__ .'/../config/boot.php';

set_exception_handler(function($exception) {
    if (PHP_SAPI == 'cli')   // run in shell
        die( (string)$exception );

    list($code, $header) = \Lysine\__on_exception($exception, false);

    if (!headers_sent())
        foreach ($header as $h) header($h);

    // ajax方式下，如果DEBUG为true
    // 可以在http header里面看到exception输出
    // 根据各种不同的http status也可以大概判断是什么地方出问题
    if (req()->isAjax()) die(1);

    ob_start();
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

// 尽快返回结果给fastcgi，剩下的环境清理工作已经和客户端无关
// php-fpm fastcgi特性
//
// 如果使用FirePHP/FireLogger这种通过header传递数据的调试工具
// 由于这些工具的header输出步骤都是注册到register_shutdown_function()
// 所以会在request finish之后才产生，客户端无法拿到调试信息，调试时需要注释掉这个
if (PHP_SAPI == 'fpm-fcgi')
    fastcgi_finish_request();
