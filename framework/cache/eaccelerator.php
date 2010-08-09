<?php
namespace Lysine\Cache;

use Lysine\ICache;

class Eaccelerator implements ICache {
    protected $life_time = 60;

    public function __construct(array $config = array()) {
        if (!extension_loaded('eaccelerator'))
            throw new \RuntimeException('Require EACCELERATOR extension');

        foreach ($config as $option => $value) $this->$option = $value;
    }

    public function set($key, $val, $life_time = null) {
        if ($life_time === null) $life_time = $this->life_time;
        return eaccelerator_put($key, $val, $life_time);
    }

    public function get($key) {
        return eaccelerator_get($key);
    }

    public function delete($key) {
        return eaccelerator_rm($key);
    }
}
