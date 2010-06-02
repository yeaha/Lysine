<?php
function app() {
    return Ly_Application::instance();
}

function cfg() {
    $app = app();
    $args = func_get_args();

    return call_user_func_array(array($app, 'getConfig'), $args);
}

function req() {
    return Ly_Request::instance();
}

function rep() {
    return Ly_Response::instance();
}
