<?php
namespace Lysine\Storage;

use MongoCollection;
use Lysine\IStorage;
use Lysine\Storage\Mongo\Select;

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
        parent::__construct($dsn, $options);
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

    public function select($target) {
        if ( !($target instanceof MongoCollection) ) {
            if (!is_array($target) || count($target) != 2)
                throw new \InvalidArgumentException();
            list($db, $collection) = $target;
            $target = $this->selectCollection($db, $collection);
        }

        return new Select($target);
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
        if (isset($config['dsn'])) {
            $dsn = $config['dsn'];
        } elseif (isset($config['servers']) && is_array($config['servers'])) {
            $servers = array();
            foreach ($config['servers'] as $server) {
                if (is_array($server))
                    $server = implode(':', $server);
                $servers[] = $server;
            }
            $dsn = 'mongodb://'. implode(',', $servers);
        } else {
            $server = isset($config['server']) ? $config['server'] : ini_get('mongo.default_host');
            $port = isset($config['port']) ? $config['port'] : ini_get('mongo.default_port');
            $dsn = sprintf('mongodb://%s:%s', $server, $port);
        }

        $options = (isset($config['options']) && is_array($config['options']))
                 ? $config['options']
                 : array();

        return array($dsn, $options);
    }
}
