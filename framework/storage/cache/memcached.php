<?php
namespace Lysine\Storage\Cache;

use Lysine\Storage\Cache;

class Memcached extends Cache {
    protected $memcached;

    protected $default_server = array('127.0.0.1', 11211);

    protected $life_time = 0;

    public function __construct(array $config) {
        if (!extension_loaded('memcached'))
            throw new \RuntimeException('Require memcached extension');

        $memcached = new \Memcached();

        if ($server = array_get($config, 'server')) {
            call_user_func_array(array($memcached, 'addServer'), $server);
        } elseif ($servers = array_get($config, 'servers')) {
            $memcached->addServers($servers);
        } else {
            call_user_func_array(array($memcached, 'addServer'), $this->default_server);
        }

        if (isset($config['life_time'])) $this->life_time = $config['life_time'];

        if (isset($config['prefix'])) $this->setPrefix($config['prefix']);

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
        $key = $this->makeKey($key);
        return $this->memcached->set($key, $val, $life_time);
    }

    public function mset(array $data, $life_time = null) {
        $life_time = $life_time ? (time() + $life_time) : 0;

        $d = array();
        foreach ($data as $key => $val) {
            $key = $this->makeKey($key);
            $d[$key] = $val;
        }

        return $this->memcached->setMulti($d, $life_time);
    }

    public function get($key) {
        $key = $this->makeKey($key);
        var_dump($key);
        return $this->memcached->get($key);
    }

    public function mget(array $keys) {
        foreach ($keys as $idx => $key)
            $keys[$idx] = $this->makeKey($key);
        return $this->memcached->getMulti($keys);
    }

    public function delete($key) {
        $key = $this->makeKey($key);
        return $this->memcached->delete($key);
    }

    public function mdelete(array $keys) {
        foreach ($keys as $key) $this->delete($key);
    }
}
