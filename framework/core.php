<?php
namespace Lysine;

use Lysine\MVC\Response;

const DIR = __DIR__;

class Config {
    static protected $config = array();

    static public function import(array $config) {
        self::$config = array_merge(self::$config, $config);
    }

    static public function set() {
        $path = func_get_args();
        $val = array_pop($path);
        return array_set(self::$config, $path, $val);
    }

    static public function get($path) {
        $path = is_array($path) ? $path : func_get_args();
        return $path ? array_get(self::$config, $path) : self::$config;
    }
}

class Error extends \Exception {
    private $more = array();

    public function __construct($message, $code = 0, $more = null) {
        $previous = null;

        if (is_array($more)) {
            $this->more = $more;
        } elseif ($more && $more instanceof \Exception) {
            $previous = $more;
        }

        parent::__construct($message, $code, $previous);
    }

    public function __get($key) {
        return array_key_exists($key, $this->more) ? $this->more[$key] : false;
    }

    public function __set($key, $val) {
        $this->more[$key] = $val;
    }

    public function __isset($key) {
        return array_key_exists($key, $this->more);
    }

    public function getMore() {
        return $this->more;
    }

    static public function invalid_argument($function, $class = null) {
        if ($class) $function = "{$class}::{$function}";
        return new self("Invalid argument of {$function}");
    }

    static public function call_undefined($function, $class = null) {
        if ($class) $function = "{$class}::{$function}";
        return new self("Call to undefined {$function}");
    }

    static public function undefined_property($class, $property) {
        return new self("Undefined property {$property} of {$class}");
    }

    static public function not_callable($function) {
        return new self("{$function} is not callable");
    }

    static public function file_not_found($file) {
        return new self("{$file} is not exist or readable");
    }
}

class StorageError extends Error {
    static public function undefined_storage($storage_name) {
        return new self('Undefined storage service:'. $storage_name);
    }

    static public function connect_failed($storage_name) {
        return new self("Connect failed! Storage service: {$storage_name}");
    }

    static public function operate_failed($storage_name, $operate, array $more = array()) {
        $message = "Operate failed! Storage: {$storage_name}, Operate: {$operate}";
        $more['storage'] = $storage_name;
        $more['operate'] = $operate;
        return new self($message, 0, $more);
    }
}

class OrmError extends Error {
}

class HttpError extends Error {
    public function getHeader() {
        return Response::httpStatus($this->getCode());
    }

    static public function bad_request() {
        return new self('Bad Request', 400);
    }

    static public function unauthorized() {
        return new self('Unauthorized', 401);
    }

    static public function forbidden() {
        return new self('Forbidden', 403);
    }

    static public function page_not_found($url) {
        return new self('Page Not Found', 404, array('url' => $url));
    }

    static public function method_not_allowed($method) {
        return new self('Method Not Allowed', 405, array('method' => $method));
    }

    static public function not_acceptable($more = null) {
        return new self('Not Acceptable', 406, $more);
    }

    static public function internal_server_error() {
        return new self('Internal Server Error', 500);
    }
}

function autoload($class) {
    static $files = null;
    if ($files === null) $files = require \Lysine\DIR . '/class_files.php';

    if (substr($class, 0, 1) == '\\') $class = ltrim($class, '\\');

    if (!array_key_exists($class, $files)) return false;
    $file = \Lysine\DIR .'/'. $files[$class];
    if (!is_readable($file)) return false;

    include $file;
    return class_exists($class, false) || interface_exists($class, false);
}
spl_autoload_register('Lysine\autoload');

require DIR .'/functions.php';
