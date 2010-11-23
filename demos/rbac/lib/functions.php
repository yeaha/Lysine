<?php
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
