<?php
use Lysine\MVC;
use Lysine\Storage\DB\Adapter\Pgsql;
use Lysine\Storage\DB\Expr;
use Lysine\Utils\Events;
use Lysine\Utils\Logging;

if (!function_exists('app')) {
    function app() {
        return MVC\Application::instance();
    }
}

if (!function_exists('req')) {
    function req() {
        return MVC\Request::instance();
    }
}

if (!function_exists('resp')) {
    function resp() {
        return MVC\Response::instance();
    }
}

if (!function_exists('render_view')) {
    function render_view($file, $vars = null) {
        static $view;
        if (!$view) $view = new MVC\View;
        return $view->reset()->render($file, $vars);
    }
}

function cfg($path = null) {
    $path = is_array($path) ? $path : func_get_args();
    return \Lysine\Config::get($path);
}

function set_header($name, $val = null) {
    return resp()->setHeader($name, $val);
}

function set_session($name, $val) {
    return resp()->setSession($name, $val);
}

function set_cookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false) {
    return resp()->setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
}

function get($key = null, $default = null) {
    if ($key === null) return $_GET;
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function post($key = null, $default = null) {
    if ($key === null) return $_POST;
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function put($key = null, $default = null) {
    static $_PUT = null;

    if ($_PUT === null) {
        if (req()->isPUT()) {
            if (strtoupper(server('request_method')) == 'PUT') {
                parse_str(file_get_contents('php://input'), $_PUT);
            } else {
                $_PUT =& $_POST;
            }
        } else {
            $_PUT = array();
        }
    }

    if ($key === null) return $_PUT;
    return isset($_PUT[$key]) ? $_PUT[$key] : $default;
}

function request($key = null, $default = null) {
    if ($key === null) return array_merge(put(), $_REQUEST);
    return isset($_REQUEST[$key]) ? $_REQUEST[$key] : put($key, $default);
}

function has_get($key) {
    return array_key_exists($key, $_GET);
}

function has_post($key) {
    return array_key_exists($key, $_POST);
}

function has_put($key) {
    return array_key_exists($key, put());
}

function has_request($key) {
    return array_key_exists($key, $_REQUEST);
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

function session($key = null, $default = false) {
    if (!isset($_SESSION)) session_start();
    if ($key === null) return $_SESSION;
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

function cookie($key = null, $default = false) {
    if (!isset($_COOKIE)) return array();
    if ($key === null) return $_COOKIE;
    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
}

function logger($name) {
    return Logging::getLogger($name);
}

function storage($name = null) {
    $pool = \Lysine\Storage\Pool::instance();
    $args = func_get_args();
    return call_user_func_array($pool, $args);
}

function dbexpr($expr) {
    if ($expr instanceof Expr) return $expr;
    return new Expr($expr);
}

// 把postgresql数组转换为php数组
function pg_decode_array($pg_array) {
    return Pgsql::decodeArray($pg_array);
}

// 把php数组转换为postgresql数组
function pg_encode_array($php_array) {
    return Pgsql::encodeArray($php_array);
}

// 把postgresql hstore数据转换为php数组
function pg_decode_hstore($hstore) {
    return Pgsql::decodeHstore($hstore);
}

// 把转换php数组为postgresql hstore数据
function pg_encode_hstore($php_array, $new_style = false) {
    return Pgsql::encodeHstore($php_array, $new_style);
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
    $args = ($args === null)
          ? array()
          : (is_array($args) ? $args : array_slice(func_get_args(), 2));
    return Events::instance()->fire($obj, $event, $args);
}

// 监听对象事件
function listen_event($obj, $event, $callback) {
    return Events::instance()->listen($obj, $event, $callback);
}

// 订阅类事件
function subscribe_event($class, $event, $callback) {
    return Events::instance()->subscribe($class, $event, $callback);
}

// 取消监听事件
function clear_event($obj, $event = null) {
    return Events::instance()->clear($obj, $event);
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

// 是关联数组还是普通数组
function is_assoc_array($array) {
    $keys = array_keys($array);
    return array_keys($keys) !== $keys;
}

// 计算分页 calculate page
function cal_page($total, $page_size, $current_page = 1) {
    $page_count = ceil($total / $page_size) ?: 1;

    if ($current_page > $page_count) {
        $current_page = $page_count;
    } elseif ($current_page < 1) {
        $current_page = 1;
    }

    $page = array(
        'total' => $total,
        'size' => $page_size,
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
