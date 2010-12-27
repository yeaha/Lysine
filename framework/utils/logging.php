<?php
namespace Lysine\Utils;

use Lysine\Error;
use Lysine\Utils\Logging\IHandler;

class Logging {
    const CRITICAL = 50;
    const ERROR = 40;
    const WARNING = 30;
    const INFO = 20;
    const DEBUG = 10;
    const NOTEST = 0;

    static private $logger = array();

    private $datefmt = '%Y-%m-%d %H:%M:%S';
    private $level = 30;
    private $handler = array();

    public function setLevel($level) {
        $this->level = (int)$level;
        return $this;
    }

    public function getLevelName($level) {
        $all = array(
            50 => 'CRITICAl',
            40 => 'ERROR',
            30 => 'WARNING',
            20 => 'INFO',
            10 => 'DEBUG',
            0 => 'NOTEST',
        );

        return isset($all[$level]) ? $all[$level] : $level;
    }

    public function addHandler(IHandler $handler) {
        $this->handler[] = $handler;
        return $this;
    }

    public function log($message, $level = null) {
        $level = ($level === null) ? $this->level : $level;
        if ($level < $this->level) return;

        if (is_array($message)) {
            foreach ($message as $msg) $this->log($msg, $level);
            return;
        }

        if (!$this->handler) return;

        $record = array(
            'time' => strftime($this->datefmt, time()),
            'message' => $message,
            'level' => $level,
            'level_name' => $this->getLevelName($level)
        );

        foreach ($this->handler as $handler) $handler->emit($record);
    }

    public function critical($message) {
        $this->log($message, self::CRITICAL);
    }

    public function error($message) {
        $this->log($message, self::ERROR);
    }

    public function warning($message) {
        $this->log($message, self::WARNING);
    }

    public function info($message) {
        $this->log($message, self::INFO);
    }

    public function debug($message) {
        $this->log($message, self::DEBUG);
    }

    // 获得日志对象实例
    // $db_log = Logging::getLogger('db');
    // $user_log = Logging::getLogger('db.user');
    // user_log会继承db_log的配置
    static public function getLogger($name = '__LYSINE__') {
        if (isset(self::$logger[$name])) return self::$logger[$name];

        $pos = strrpos($name, '.');
        if ($pos !== false) {
            $parent = substr($name, 0, $pos);
            if (!isset(self::$logger[$parent]))
                throw new Error('Undefined parent logger ['. $parent .']');
            $logger = clone self::$logger[$parent];
        } else {
            $logger = new self();
        }

        return self::$logger[$name] = $logger;
    }
}

namespace Lysine\Utils\Logging;

use Lysine\Error;
use Lysine\Storage\File;
use Lysine\Utils\Logging;

class FileHandler implements IHandler {
    private $storage;

    public function __construct($storage) {
        if (is_object($storage) && !($storage instanceof File) )
            throw new Error('Invalid File Logger Handler, need Lysine\Storage\File');

        if (is_string($storage)) $storage = storage($storage);

        $this->storage = $storage;
    }

    public function emit(array $record) {
        $this->storage->write($record['time'] .' '. $record['message']);
    }
}

// http://www.firephp.org/
class FirePHPHandler implements IHandler {
    private $handler;

    public function __construct() {
        if (!class_exists('FirePHP'))
            throw new Error('Require FirePHP class');
        $this->handler = \FirePHP::getInstance(true);
    }

    public function __call($method, $args) {
        return call_user_func_array(array($this->handler, $method), $args);
    }

    public function emit(array $record) {
        switch ($record['level']) {
            case Logging::INFO: return $this->handler->info($record['message']);
            case Logging::WARNING: return $this->handler->warn($record['message']);
            case Logging::ERROR: return $this->handler->error($record['message']);
            case Logging::CRITICAL: return $this->handler->error($record['message']);
            default: return $this->handler->log($record['message']);
        }
    }
}

// http://firelogger.binaryage.com/
class FireLoggerHandler implements IHandler {
    private $handler;

    public function __construct() {
        if (!class_exists('FireLogger'))
            throw new Error('Require FireLogger class');

        defined('FIRELOGGER_NO_CONFLICT') or define('FIRELOGGER_NO_CONFLICT', true);
        defined('FIRELOGGER_NO_DEFAULT_LOGGER') or define('FIRELOGGER_NO_DEFAULT_LOGGER', true);
        defined('FIRELOGGER_NO_EXCEPTION_HANDLER') or define('FIRELOGGER_NO_EXCEPTION_HANDLER', true);
        defined('FIRELOGGER_NO_ERROR_HANDLER') or define('FIRELOGGER_NO_ERROR_HANDLER', true);
        $this->handler = new \FireLogger('php', 'background-color: #767ab6');
    }

    public function __call($method, $args) {
        return call_user_func_array(array($this->handler, $method), $args);
    }

    public function emit(array $record) {
        switch ($record['level']) {
            case Logging::INFO: return $this->handler->log('info', $record['message']);
            case Logging::WARNING: return $this->handler->log('warning', $record['message']);
            case Logging::ERROR: return $this->handler->log('error', $record['message']);
            case Logging::CRITICAL: return $this->handler->log('critical', $record['message']);
            default: return $this->handler->log($record['message']);
        }
    }
}

// http://www.chromephp.com/
class ChromePHPHandler implements IHandler {
    public function __construct() {
        if (!class_exists('ChromePHP'))
            throw new Error('Require ChromePHP class');
    }

    public function emit(array $record) {
        if (in_array($record['level'], array(Logging::ERROR, Logging::CRITICAL))) {
            return \ChromePHP::error($record['message']);
        } elseif ($record['level'] == Logging::WARNING) {
            return \ChromePHP::warn($record['message']);
        } else {
            return \ChromePHP::log($record['message']);
        }
    }
}
