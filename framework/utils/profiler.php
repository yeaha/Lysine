<?php
namespace Lysine\Utils;

class Profiler {
    static private $instance;

    private $stack = array();

    private $time = array();

    public function start($name) {
        $this->stack[] = array($name, microtime(true));
    }

    public function end($all = false) {
        if (!$this->stack) return false;

        while ($all) {
            if (!$this->end(false)) return true;
        }

        list($name, $start_time) = array_pop($this->stack);
        $this->time[$name] = microtime(true) - $start_time;
        return true;
    }

    public function getRuntime($name = null) {
        if ($name === null) return $this->time;
        return isset($this->time[$name]) ? $this->time[$name] : false;
    }

    public function __toString() {
        $lines = array();
        foreach ($this->time as $name => $use_time) {
            $lines[] = sprintf('%s: %ss', $name, $use_time);
        }

        return implode(PHP_EOL, $lines);
    }

    static public function instance() {
        return self::$instance ?: (self::$instance = new static);
    }
}
