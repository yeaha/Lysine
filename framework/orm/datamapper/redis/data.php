<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM\DataMapper\Data;
use Lysine\ORM\DataMapper\RedisMapper;

class RedisData extends Data {
    static public function getMapper() {
        return RedisMapper::factory(get_called_class());
    }
}
