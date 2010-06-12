<?php
function app() {
    return Ly_Application::instance();
}

function cfg() {
    $args = func_get_args();
    return call_user_func_array(array(app(), 'getConfig'), $args);
}

function req() {
    static $instance;

    if (!$instance) {
        $class = cfg('app', 'request_class');
        $instance = new $class;
        if ( !($instance instanceof Ly_Request) )
            throw new Ly_Exception('Invalid request class');
    }

    return $instance;
}

if (!function_exists('render_view')) {
    function render_view($file, array $vars = null) {
        $render = new Ly_View_Render(cfg('view'));
        return $render->fetch($file, $vars);
    }
}

if (!function_exists('db')) {
    function db($dsn_name = null) {
        return Ly_Db::conn($dsn_name);
    }
}

function dbexpr($expr) {
    return new Ly_Db_Expr($expr);
}

function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

/**
 * 根据key路径，在array中找出结果
 * 如果key不存在，返回false
 *
 * @param array $target
 * @param array $path
 * @return mixed
 */
function array_spider(array $target, array $path = array()) {
    while (list(, $key) = each($path)) {
        if (!is_array($target)) return false;
        if (!array_key_exists($key, $target)) return false;
        $target = $target[$key];
    }

    return $target;
}

function add($a, $b) {
    return $a + $b;
}

function sub($a, $b) {
    return $a - $b;
}

function mul($a, $b) {
    return $a * $b;
}

function div($a, $b) {
    return $a / $b;
}

function mod($a, $b) {
    return $a % $b;
}

function divmod($a, $b) {
    return array(floor(div($a, $b)), mod($a, $b));
}

function inc($n) {
    return $n + 1;
}

function dec($n) {
    return $n - 1;
}

function eq($a, $b) {
    return $a === $b;
}

function comp($a, $b) {
    return $a == $b;
}

function great($a, $b) {
    return $a > $b;
}

function less($a, $b) {
    return $a < $b;
}

function negative($n) {
    return $n < 0;
}

function positive($n) {
    return $n > 0;
}
