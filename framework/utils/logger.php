<?php
namespace Lysine\Utils;

use Lysine\Config;

class Logger extends Singleton {
    // {{{ 日志级别，越高越详细
    const NONE = 0;
    const ERROR = 1;
    const WARNING = 2;
    const NOTICE = 3;
    const DEBUG = 4;
    // }}}

    static public $level_name = array(
        1 => 'ERROR',
        2 => 'WARNING',
        3 => 'NOTICE',
        4 => 'DEBUG',
    );

    private $file;

    private $time_format = '%Y-%m-%d %H:%M:%S';

    private $level = 0;

    private $buffer_size = 4096;

    private $content_size;

    private $logs = array();

    protected function __construct() {
        $config = Config::get('app', 'logger');
        if (!$config) $config = array();

        foreach ($config as $prop => $val) $this->$prop = $val;

        $this->file = strftime($config['file'], time());
        $this->level = isset($config['level']) ? $config['level'] : self::WARNING;
        $this->content_size = 0;
    }

    public function __destruct() {
        $this->flush();
    }

    public function log($message, $level) {
        if ($level < $this->level) return;

        if (is_array($message)) {
            foreach ($message as $msg) {
                $this->logs[] = array($msg, $level);
                $this->content_size += strlen($msg);
            }
        } else {
            $this->logs[] = array($message, $level);
            $this->content_size += strlen($message);
        }

        if ($this->content_size >= $this->buffer_size)
            $this->flush();
    }

    private function flush() {
        if (!$this->content_size) return true;

        $time = strftime($this->time_format, time());
        $lines = array();
        foreach ($this->logs as $log) {
            list($message, $level) = $log;
            $level = self::$level_name[$level];
            $lines[] = "{$level} {$time}: {$message}";
        }

        if (!$fp = fopen($this->file, 'a')) return false;

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, implode("\n", $lines) ."\n");
            flock($fp, LOCK_UN);
            $this->logs = array();
            $this->content_size = 0;
        }

        fclose($fp);
    }

    static public function debug($message) {
        static::instance()->log($message, self::DEBUG);
    }

    static public function notice($message) {
        static::instance()->log($message, self::NOTICE);
    }

    static public function warning($message) {
        static::instance()->log($message, self::WARNING);
    }

    static public function error($message) {
        static::instance()->log($message, self::ERROR);
    }

    static public function exception(\Exception $e) {
        $logs = explode("\n", $e->__toString());
        if ($e instanceof Error && $more = $e->getMore())
            $logs[] = 'More: '. json_encode($more);

        self::warning($logs);
        if ($pe = $e->getPrevious()) self::exception($pe);
    }
}
