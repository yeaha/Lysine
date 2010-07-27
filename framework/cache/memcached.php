<?php
namespace Lysine\Cache;

use Lysine\ICache;

class Memcached implements ICache {
    protected $memcached;

    protected $default_server = array('127.0.0.1', 11211);

    protected $life_time = 0;

    public function __construct(array $config = array()) {
        if (!extension_loaded('memcached'))
            throw new \RuntimeException('Require memcached extension');

        $memcached = new Memcached();

        if ($server = array_get($config, 'server')) {
            call_user_func_array(array($memcached, 'addServer'), $server);
        } elseif ($servers = array_get($config, 'servers')) {
            $memcached->addServers($servers);
        } else {
            call_user_func_array(array($memcached, 'addServer'), $this->default_server);
        }

        if ($options = array_get($config, 'options')) {
            foreach ($options as $key => $val)
                $memcache->setOption($key, $val);
        }

        $this->memcached = $memcached;
    }

    public function __call($fn, $args) {
        call_user_func_array(array($this->memcached, $fn), $args);
    }

    public function set($key, $val, $life_time = null) {
        $life_time = $life_time ? (time() + $life_time) : 0;
        return $this->memcached->set($key, $val, $life_time);
    }

    public function get($key) {
        return $this->memcached->get($key);
    }

    public function delete($key) {
        return $this->memcached->delete($key);
    }
}
