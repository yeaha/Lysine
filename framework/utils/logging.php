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

use Lysine\Storage\File;

class FileHandler implements IHandler {
    private $storage;

    public function __construct($storage) {
        if (is_object($storage) && !($storage instanceof Lysine\Storage\File) )
            throw new Error('Invalid File Logger Handler, need Lysine\Storage\File');

        if (is_string($storage)) $storage = storage($storage);

        $this->storage = $storage;
    }

    public function emit(array $record) {
        $line = sprintf('%s %s %s', $record['time'], $record['level_name'], $record['message']);
        $this->storage->write($line);
    }
}
