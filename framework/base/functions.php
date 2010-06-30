<?php
namespace Lysine;

use Lysine\Storage\Db;
use Lysine\Utils;

function app() {
    return Application::instance();
}

function cfg() {
    $path = func_get_args();

    return call_user_func_array(array(app(), 'getConfig'), $path);
}

function req() {
    static $instance;
    if (!$instance) {
        $class = cfg('app', 'request_class');
        $instance = $class ? new $class() : new Request();
    }
    return $instance;
}

/**
 * 根据key路径，在array中找出结果
 * 如果key路径不存在，返回false
 *
 * @param array $target
 * @param mixed $path
 * @access public
 * @return mixed
 */
function array_spider(array $target, $path) {
    $path = is_array($path) ? $path : array_slice(func_get_args(), 1);

    while (list(, $key) = each($path)) {
        if (!is_array($target)) return false;
        if (!array_key_exists($key, $target)) return false;
        $target = $target[$key];
    }

    return $target;
}

function dbexpr($expr) {
    return new Db\Expr($expr);
}

function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

function coll() {
    $elements = func_get_args();
    return new Utils\Coll($elements);
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
