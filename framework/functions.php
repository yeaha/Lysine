<?php
function app() {
    return \Lysine\MVC\Application::instance();
}

function cfg($path = null) {
    $path = is_array($path) ? $path : func_get_args();
    return \Lysine\Config::get($path);
}

function req() {
    static $instance;
    if (!$instance) {
        $class = cfg('app', 'request_class');
        $instance = $class ? new $class() : \Lysine\MVC\Request::instance();
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

function storage($name = null) {
    $pool = \Lysine\Storage\Pool::instance();
    $args = func_get_args();
    return call_user_func_array($pool, $args);
}

function dbexpr($expr) {
    return new \Lysine\Storage\DB\Expr($expr);
}

// 把postgresql数组转换为php数组
function pg_decode_array($pg_array) {
    return \Lysine\Storage\Db\Adapter\Pgsql::decodeArray($pg_array);
}

// 把php数组转换为postgresql数组
function pg_encode_array($php_array) {
    return \Lysine\Storage\Db\Adapter\Pgsql::encodeArray($php_array);
}

// 把postgresql hstore数据转换为php数组
function pg_decode_hstore($hstore) {
    return \Lysine\Storage\Db\Adapter\Pgsql::decodeHstore($hstore);
}

// 把转换php数组为postgresql hstore数据
function pg_encode_hstore($php_array) {
    return \Lysine\Storage\Db\Adapter\Pgsql::encodeHstore($php_array);
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

    foreach ($path as $key) {
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

    foreach ($path as $p) {
        if (!is_array($target)) return false;
        if (!array_key_exists($p, $target)) $target[$p] = array();
        $target =& $target[$p];
    }

    $target[$key] = $val;
    return true;
}

// 触发对象事件
function fire_event($obj, $event, $args = null) {
    $args = is_array($args) ? $args : array_slice(func_get_args(), 2);
    return \Lysine\Utils\Events::instance()->fire($obj, $event, $args);
}

// 监听对象事件
function listen_event($obj, $event, $callback) {
    return \Lysine\Utils\Events::instance()->listen($obj, $event, $callback);
}

// 订阅类事件
function subscribe_event($class, $callback) {
    return \Lysine\Utils\Events::instance()->subscribe($class, $callback);
}

// 取消监听事件
function clear_event($obj, $event = null) {
    return \Lysine\Utils\Events::instance()->clear($obj, $event);
}

function start_with($haystack, $needle, $case_insensitive = false) {
    if ($case_insensitive) {
        return stripos($haystack, $needle) === 0;
    } else {
        return strpos($haystack, $needle) === 0;
    }
}

// 检查对象实例或者类名是否属于指定名字空间
function in_namespace($class, $namespace) {
    if (is_object($class)) $class = get_class($class);
    $class = ltrim($class, '\\');
    $namespace = trim($namespace, '\\') . '\\';
    return start_with($class, $namespace, true);
}

function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
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
