<?php
function __on_exception($exception) {
    $code = \Lysine\__on_exception($exception);
    require_once ROOT_DIR .'/public/_error/500.php';
    die(1);
}

function __on_error($errno, $errstr, $errfile, $errline, $errcontext) {
    throw new Error($errstr, $errno, null, array(
        'file' => $errfile,
        'line' => $errline,
        'context' => $errcontext,
    ));
}
