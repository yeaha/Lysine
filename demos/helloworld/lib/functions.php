<?php
function render_view($view_name, array $vars = null) {
    static $view;
    if (!$view) $view = new Lysine\MVC\View(cfg('app', 'view'));

    return $view->reset()->render($view_name, $vars);
}

function __on_exception($exception) {
    $code = $exception instanceof \Lysine\HttpError
          ? $exception->getCode()
          : 500;
    header(\Lysine\MVC\Response::httpStatus($code));

    require_once ROOT_DIR .'/public/_error/500.php';
    die($code);
}

function __on_error($errno, $errstr, $errfile, $errline, $errcontext) {
    throw new Error($errstr, $errno, null, array(
        'file' => $errfile,
        'line' => $errline,
        'context' => $errcontext,
    ));
}
