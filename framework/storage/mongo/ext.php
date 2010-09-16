<?php
namespace Lysine\Storage\Mongo;

/**
 * MongoDB类扩展
 *
 * @uses MongoDB
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class DB extends \MongoDB {
    public function __construct($conn, $name) {
        parent::__construct($conn, $name);
    }

    public function __get($name) {
        return $this->selectCollection($name);
    }

    public function selectCollection($name) {
        return new Collection($this, $name);
    }
}

/**
 * MongoCollection扩展
 *
 * @uses MongoCollection
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Collection extends \MongoCollection {
    public function __construct($db, $name) {
        parent::__construct($db, $name);
    }

    public function __get($name) {
        $db = parent::__get('db');
        return $db->selectCollection($name);
    }

    public function find(array $query = array(), array $fields = array()) {
        $cursor = parent::find($query, $fields);
        return new Cursor($cursor);
    }
}

/**
 * MongoCursor装饰
 * 
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Cursor {
    private $cursor;

    public function __construct($cursor) {
        $this->cursor = $cursor;
    }

    public function __call($method, $args) {
        $result = call_user_func_array(array($this->cursor, $method), $args);
        if ($result === $this->cursor) return $this;
        return $result;
    }
}
