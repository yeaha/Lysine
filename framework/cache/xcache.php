<?php
namespace Lysine\Cache;

use Lysine\ICache;

class Xcache implements ICache {
    protected $life_time = 60;

    public function __construct(array $config = array()) {
        if (!extension_loaded('xcache'))
            throw new \RuntimeException('Require XCACHE extension');

        foreach ($config as $option => $value) $this->$option = $value;
    }

    public function set($key, $val, $life_time = null) {
        if ($life_time === null) $life_time = $this->life_time;
        return xcache_set($key, $val, $life_time);
    }

    public function mset(array $data, $life_time = null) {
        foreach ($data as $key => $val) $this->set($key, $val, $life_time);
    }

    public function get($key) {
        return xcache_isset($key) ? xcache_get($key) : false;
    }

    public function mget(array $keys) {
        $result = array();
        foreach ($keys as $key) $result[$key] = $this->get($key);
        return $result;
    }

    public function delete($key) {
        return xcache_unset($key);
    }

    public function mdelete(array $keys) {
        foreach ($keys as $key) $this->delete($key);
    }
}
