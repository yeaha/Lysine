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
    if ($key === null) return $_GET;
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function post($key = null, $default = false) {
    if ($key === null) return $_POST;
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function request($key = null, $default = false) {
    if ($key === null) return $_REQUEST;
    return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
}

function env($key = null, $default = false) {
    if ($key === null) return $_ENV;
    $key = strtoupper($key);
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

function server($key = null, $default = false) {
    if ($key === null) return $_SERVER;
    $key = strtoupper($key);
    return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
}

function session() {
    if (!isset($_SESSION)) return false;
    return array_get($_SESSION, func_get_args());
}

function cookie() {
    if (!isset($_COOKIE)) return false;
    return array_get($_COOKIE, func_get_args());
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
    return \Lysine\Storage\DB\Adapter\Pgsql::decodeArray($pg_array);
}

// 把php数组转换为postgresql数组
function pg_encode_array($php_array) {
    return \Lysine\Storage\DB\Adapter\Pgsql::encodeArray($php_array);
}

// 把postgresql hstore数据转换为php数组
function pg_decode_hstore($hstore) {
    return \Lysine\Storage\DB\Adapter\Pgsql::decodeHstore($hstore);
}

// 把转换php数组为postgresql hstore数据
function pg_encode_hstore($php_array, $new_style = false) {
    return \Lysine\Storage\DB\Adapter\Pgsql::encodeHstore($php_array, $new_style);
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

// 计算分页 calculate page
function cal_page($total, $page_size, $current_page = 1) {
    $page_count = ceil($total / $page_size);
    if (!$page_count) $page_count = 1;

    $page = array(
        'total' => $total,
        'page_size' => $page_size,
        'first' => 1,
        'last' => $page_count,
        'current' => $current_page,
        'has_prev' => false,
        'prev' => null,
        'has_next' => false,
        'next' => null,
    );

    if ($current_page > $page['first']) {
        $page['has_prev'] = true;
        $page['prev'] = $current_page - 1;
    }

    if ($current_page < $page['last']) {
        $page['has_next'] = true;
        $page['next'] = $current_page + 1;
    }

    return $page;
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
