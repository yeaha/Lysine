<?php
namespace Lysine\Cache;

use Lysine\ICache;

class Memcached implements ICache {
    public function set($key, $val, $life_time = null) {
    }

    public function get($key, $callback = null) {
    }

    public function delete($key) {
    }
}
