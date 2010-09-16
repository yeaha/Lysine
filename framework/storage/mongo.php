<?php
namespace Lysine\Storage;

use MongoCollection;
use Lysine\IStorage;

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
