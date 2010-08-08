<?php
namespace Lysine\Cache;

use Lysine\ICache;

class Apc implements ICache {
    public function __construct(array $config = array()) {
    }

    public function set($key, $val, $life_time = null) {
    }

    public function get($key) {
    }

    public function delete($key) {
    }
}
