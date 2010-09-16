<?php
namespace Lysine\Storage {
    use Lysine\IStorage;
    use Lysine\Storage\Mongo\DB as MongoDB;
    use Lysine\Storage\Mongo\Collection as MongoCollection;

    /**
     * mongodb数据库连接
     *
     * @package Storage
     * @uses Mongo
     * @uses Lysine\IStorage
     * @author yangyi <yangyi.cn.gz@gmail.com>
     */
    class Mongo extends \Mongo implements IStorage {
        /**
         * 构造函数
         *
         * @param array $config
         * @access public
         * @return void
         */
        public function __construct(array $config) {
            list($dsn, $options) = self::parseConfig($config);
            if (!isset($options['persist'])) $options['persist'] = $dsn;
            parent::__construct($dsn, $options);
        }

        public function __get($dbname) {
            return $this->selectDB($dbname);
        }

        public function selectDB($name) {
            return new MongoDB($this, $name);
        }

        public function selectCollection($db, $collection) {
            return new MongoCollection($this->selectDB($db), $collection);
        }

        /**
         * 获得指定数据库的集合信息
         *
         * @param string $dbname
         * @access public
         * @return array
         */
        public function listCollections($dbname) {
            return $this->selectDB($dbname)->listCollections();
        }

        /**
         * 解析并格式化配置数据
         *
         * @param array $config
         * @static
         * @access public
         * @return array
         */
        static public function parseConfig(array $config) {
            $dsn = isset($config['dsn'])
                 ? $config['dsn']
                 : sprintf('mongodb://%s/%s', ini_get('mongo.default_host'), ini_get('mongo.default_port'));

            $options = (isset($config['options']) && is_array($config['options']))
                     ? $config['options']
                     : array();

            return array($dsn, $options);
        }
    }
}

namespace Lysine\Storage\Mongo {
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
            if ($name == 'db') return $db;
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
}
