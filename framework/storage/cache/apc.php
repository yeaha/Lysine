<?php
namespace Lysine\Storage\Cache;

use Lysine\Storage\Cache;

class Apc extends Cache {
    protected $life_time = 60;

    public function __construct(array $config) {
        if (!extension_loaded('apc'))
            throw new \RuntimeException('Require APC extension');

        parent::__construct($config);
    }

    public function set($key, $val, $life_time = null) {
        if ($life_time === null) $life_time = $this->life_time;
        $key = $this->makeKey($key);
        return apc_store($key, $val, $life_time);
    }

    public function mset(array $data, $life_time = null) {
        foreach ($data as $key => $val) $this->set($key, $val, $life_time);
    }

    public function get($key) {
        $key = $this->makeKey($key);
        return apc_fetch($key);
    }

    public function mget(array $keys) {
        $result = array();
        foreach ($keys as $key) $result[$key] = $this->get($key);
        return $result;
    }

    public function delete($key) {
        $key = $this->makeKey($key);
        return apc_delete($key);
    }

    public function mdelete(array $keys) {
        foreach ($keys as $key) $this->delete($key);
    }
}
