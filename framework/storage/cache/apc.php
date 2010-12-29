<?php
namespace Lysine\Storage\Cache;

use Lysine\Error;
use Lysine\Storage\Cache;

class Apc extends Cache {
    protected $life_time = 300;

    public function __construct(array $config) {
        if (!extension_loaded('apc'))
            throw Error::require_extension('APC');

        parent::__construct($config);
    }

    public function set($key, $val, $life_time = null) {
        if ($life_time === null) $life_time = $this->life_time;
        if (DEBUG) \Lysine\logger('cache')->debug('Apc set key '. is_array($key) ? implode(',', $key) : $key .' with life_time '. $life_time);

        $key = $this->makeKey($key);
        return apc_store($key, $val, $life_time);
    }

    public function mset(array $data, $life_time = null) {
        foreach ($data as $key => $val) $this->set($key, $val, $life_time);
    }

    public function get($key) {
        if (DEBUG) \Lysine\logger('cache')->debug('Apc get key '. is_array($key) ? implode(',', $key) : $key);

        $key = $this->makeKey($key);
        return apc_fetch($key);
    }

    public function mget(array $keys) {
        $result = array();
        foreach ($keys as $key) $result[$key] = $this->get($key);
        return $result;
    }

    public function delete($key) {
        if (DEBUG) \Lysine\logger('cache')->debug('Apc delete key '. is_array($key) ? implode(',', $key) : $key);

        $key = $this->makeKey($key);
        return apc_delete($key);
    }

    public function mdelete(array $keys) {
        foreach ($keys as $key) $this->delete($key);
    }
}
