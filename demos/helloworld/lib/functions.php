<?php
function render_view($view_name, array $vars = null) {
    static $render;
    if (!$render) $render = new Lysine\View(cfg('app', 'view'));

    return $render->reset()->fetch($view_name, $vars);
}

function __on_exception($exception) {
    $code = $exception instanceof \Lysine\Request_Exception
          ? $exception->getCode()
          : 500;
    header(\Lysine\Response::httpStatus($code));

    echo render_view('_error/500', array('exception' => $exception));
    die($code);
}
