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

    public function __construct($message, $code = 0, \Exception $previous = null, array $more = array()) {
        $this->more = $more;
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
        return new static("Invalid argument of {$function}");
    }

    static public function call_undefined($function, $class = null) {
        if ($class) $function = "{$class}::{$function}";
        return new static("Call to undefined {$function}");
    }

    static public function undefined_property($class, $property) {
        return new static("Undefined property {$property} of {$class}");
    }

    static public function not_callable($function) {
        return new static("{$function} is not callable");
    }

    static public function file_not_found($file) {
        return new static("{$file} is not exist or readable");
    }

    static public function require_extension($extension) {
        return new static("Require {$extension} extension");
    }
}

class StorageError extends Error {
    static public function undefined_storage($storage_name) {
        return new self('Undefined storage service:'. $storage_name);
    }

    static public function connect_failed($storage_name) {
        return new self("Connect failed! Storage service: {$storage_name}");
    }
}

class OrmError extends StorageError {
    static public function insert_failed($class, $record, $previous = null, array $more = array()) {
        $more = array_merge($more, array('class' => $class, 'record' => $record));
        return new static("{$class} insert failed", 0, $previous, $more);
    }

    static public function update_failed($class, $record, $previous = null, array $more = array()) {
        $more = array_merge($more, array('class' => $class, 'record' => $record));
        return new static("{$class} update failed", 0, $previous, $more);
    }

    static public function delete_failed($class, $id, $previous = null, array $more = array()) {
        $more = array_merge($more, array('class' => $class, 'primary_key' => $id));
        return new static("{$class} delete failed", 0, $previous, $more);
    }
}

class HttpError extends Error {
    public function getHeader() {
        return Response::httpStatus($this->getCode());
    }

    static public function bad_request(array $more) {
        return new self('Bad Request', 400, $more);
    }

    static public function unauthorized(array $more) {
        return new self('Unauthorized', 401, $more);
    }

    static public function forbidden(array $more) {
        return new self('Forbidden', 403, $more);
    }

    static public function page_not_found($url) {
        return new self('Page Not Found', 404, null, array('url' => $url));
    }

    static public function method_not_allowed(array $more = array()) {
        return new self('Method Not Allowed', 405, null, $more);
    }

    static public function not_acceptable(array $more) {
        return new self('Not Acceptable', 406, null, $more);
    }

    static public function conflict(array $more) {
        return new self('Conflict', 409, null, $more);
    }

    static public function precondition_failed($more) {
        return new self('Precondition Failed', 412, null, $more);
    }

    static public function internal_server_error(array $more) {
        return new self('Internal Server Error', 500, null, $more);
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
