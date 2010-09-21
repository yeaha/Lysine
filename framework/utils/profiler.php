<?php
namespace Lysine\Utils;

use Lysine\Utils\Singleton;

class Profiler extends Singleton {
    private $stack = array();

    private $time = array();

    public function start($name) {
        $this->stack[] = array($name, microtime(true));
    }

    public function end() {
        if (!$this->stack) return;
        list($name, $start_time) = array_pop($this->stack);
        $this->time[$name] = microtime(true) - $start_time;
    }

    public function getUseTime($name = null) {
        if ($name === null) return $this->time;
        return isset($this->time[$name]) ? $this->time[$name] : false;
    }

    public function __toString() {
        $lines = array();
        foreach ($this->time as $name => $use_time) {
            $lines[] = $name;
            $lines[] = 'use time: '. $use_time;
        }

        return implode(PHP_EOL, $lines);
    }
}
