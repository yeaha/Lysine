<?php
namespace Lysine\Utils;

use Lysine\Config;

class Logger implements ILogger {
    private $instance = array();

    protected $level;

    protected $buffer_size = 4096;

    public function __construct(array $config) {
        $this->level = isset($config['level']) ? $config['level'] : self::WARNING;
        $this->file = strftime($config['file'], time());
        if (isset($config['buffer_size'])) $this->buffer_size = $config['buffer_size'];
    }

    public function debug($message) {
        $this->log($message, self::DEBUG);
    }

    public function notice($message) {
        $this->log($message, self::NOTICE);
    }

    public function warning($message) {
        $this->log($message, self::WARNING);
    }

    public function error($message) {
        $this->log($message, self::ERROR);
    }

    public function log($message, $level) {
        if ($level > $this->level) return;
    }

    public function flush() {
    }

    static public function instance($name) {
        if (!isset(self::$instance[$name])) {
            $config = Config::get('logger', $name);
            self::$instance[$name] = new self($config);
        }

        return self::$instance[$name];
    }
}
