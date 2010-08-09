<?php
namespace Lysine\Cache;

use Lysine\ICache;

class Apc implements ICache {
    protected $life_time = 60;

    public function __construct(array $config = array()) {
        if (!extension_loaded('apc'))
            throw new \RuntimeException('Require APC extension');

        foreach ($config as $option => $value) $this->$option = $value;
    }

    public function set($key, $val, $life_time = null) {
        if ($life_time === null) $life_time = $this->life_time;
        return apc_store($key, $val, $life_time);
    }

    public function get($key) {
        return apc_fetch($key);
    }

    public function delete($key) {
        return apc_delete($key);
    }
}
