<?php
namespace Lysine\Storage\Cache;

use Lysine\Error;
use Lysine\Storage\Cache;

class Memcached extends Cache {
    protected $memcached;

    protected $default_server = array('127.0.0.1', 11211);

    protected $life_time = 300;

    public function __construct(array $config) {
        if (!extension_loaded('memcached'))
            throw Error::require_extension('memcached');

        $memcached = new \Memcached();

        if (isset($config['servers'])) {
            $memcached->addServers($config['servers']);
        } else {
            list($host, $port) = (array_get($config, 'server') ?: $this->default_server);
            $memcached->addServer($host, $port);
        }

        if (isset($config['life_time'])) $this->life_time = $config['life_time'];
        if (isset($config['prefix'])) $this->setPrefix($config['prefix']);

        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $val)
                $memcache->setOption($key, $val);
        }

        $this->memcached = $memcached;
    }

    public function __call($fn, $args) {
        call_user_func_array(array($this->memcached, $fn), $args);
    }

    public function set($key, $val, $life_time = null) {
        $life_time = $life_time ? (time() + $life_time) : 0;
        \Lysine\logger('cache')->debug('Memcached set key '. is_array($key) ? implode(',', $key) : $key .' with life_time '. $life_time);

        $key = $this->makeKey($key);
        return $this->memcached->set($key, $val, $life_time);
    }

    public function mset(array $data, $life_time = null) {
        $life_time = $life_time ? (time() + $life_time) : 0;
        \Lysine\logger('cache')->debug('Memcached set multiple keys '. implode(',', array_keys($data)) .' with life_time '. $life_time);

        $d = array();
        foreach ($data as $key => $val) {
            $key = $this->makeKey($key);
            $d[$key] = $val;
        }

        return $this->memcached->setMulti($d, $life_time);
    }

    public function get($key) {
        \Lysine\logger('cache')->debug('Memcached get key '. is_array($key) ? implode(',', $key) : $key);

        $key = $this->makeKey($key);
        return $this->memcached->get($key);
    }

    public function mget(array $keys) {
        \Lysine\logger('cache')->debug('Memcached get multiple keys '. implode(',', $keys));

        foreach ($keys as $idx => $key)
            $keys[$idx] = $this->makeKey($key);
        return $this->memcached->getMulti($keys);
    }

    public function delete($key) {
        \Lysine\logger('cache')->debug('Memcached delete key '. is_array($key) ? implode(',', $key) : $key);

        $key = $this->makeKey($key);
        return $this->memcached->delete($key);
    }

    public function mdelete(array $keys) {
        foreach ($keys as $key) $this->delete($key);
    }
}
