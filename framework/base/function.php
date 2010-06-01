<?php
function app() {
    return Ly_Application::instance();
}

function req() {
    return Ly_Request::instance();
}

function rep() {
    return Ly_Response::instance();
}
