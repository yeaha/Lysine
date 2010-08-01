<?php
function app() {
    return \Lysine\Application::instance();
}

function cfg($path = null) {
    $path = is_array($path) ? $path : func_get_args();
    return forward_static_call_array(array('Lysine\Config', 'get'), $path);
}

function req() {
    static $instance;
    if (!$instance) {
        $class = cfg('app', 'request_class');
        $instance = $class ? new $class() : new \Lysine\Request();
    }
    return $instance;
}

function get($key = null, $default = false) {
    return req()->get($key, $default);
}

function post($key = null, $default = false) {
    return req()->post($key, $default);
}

function request($key = null, $default = false) {
    return req()->request($key, $default);
}

function env($key = null, $default = false) {
    return req()->env($key, $default);
}

function server($key = null, $default = false) {
    return req()->server($key, $default);
}

function session() {
    $args = func_get_args();
    return call_user_func_array(array(req(), 'session'), $args);
}

function cookie() {
    $args = func_get_args();
    return call_user_func_array(array(req(), 'cookie'), $args);
}

function db($node_name = null) {
    return \Lysine\Db\Pool::instance()->getAdapter($node_name);
}

function url() {
    static $router_class;
    if (!$router_class) $router_class = get_class(app()->getRouter());

    $args = func_get_args();
    return forward_static_call_array(array($router_class, 'url'), $args);
}

/**
 * 根据key路径，在array中找出结果
 * 如果key路径不存在，返回false
 *
 * Example:
 * array_get($test, 'a', 'b', 'c');
 * 等于
 * $test['a']['b']['c']
 *
 * @param array $target
 * @param mixed $path
 * @access public
 * @return mixed
 */
function array_get($target, $path) {
    if (!is_array($target)) {
        trigger_error('array_get() excepts parameter 1 to be array', E_WARNING);
        return false;
    }

    $path = is_array($path) ? $path : array_slice(func_get_args(), 1);

    while (list(, $key) = each($path)) {
        if (!is_array($target)) return false;
        if (!array_key_exists($key, $target)) return false;

        $target =& $target[$key];
    }

    return $target;
}

/**
 * 根据key路径，设置array内的值
 *
 * Example:
 * array_set($test, 'a', 'b', 'c');
 * 等于
 * $test['a']['b'] = 'c';
 *
 * @param mixed $target
 * @param mixed $path
 * @param mixed $val
 * @access public
 * @return void
 */
function array_set(&$target, $path, $val) {
    if (!is_array($target)) {
        trigger_error('array_set() excepts parameter 1 to be array', E_WARNING);
        return false;
    }

    if (is_array($path)) {
        $key = array_pop($path);
    } else {
        $path = array_slice(func_get_args(), 1);
        list($key, $val) = array_splice($path, -2, 2);
    }

    while (list(, $p) = each($path)) {
        if (!is_array($target)) return false;
        if (!array_key_exists($p, $target)) $target[$p] = array();
        $target =& $target[$p];
    }

    $target[$key] = $val;
    return true;
}

function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

function coll() {
    $elements = func_get_args();
    return new \Lysine\Utils\Coll($elements);
}

/**
 * 当前unix timestamp
 * 可指定format格式化返回值
 *
 * @param string $format
 * @access public
 * @return mixed
 */
function now($format = null) {
    static $now = null;
    if ($now === null) $now = time();
    return $format ? date($format, $now) : $now;
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
