<?php
namespace Lysine\Orm\DataMapper;

use Lysine\Orm\DataMapper\Mapper;

class Meta {
    static private $instance = array();

    private $class;

    private function __construct($class) {
        $this->class = $class;
    }

    public function getPrimaryKey() {
    }

    public function getCollection() {
    }

    static public function factory($class) {
        if (is_object($class)) $class = get_class($class);

        if (!isset(self::$instance[$class])) self::$instance[$class] = new self($class);
        return self::$instance[$class];
    }
}
