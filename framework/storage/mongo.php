<?php
namespace Lysine\Storage;

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
     * 查询collection
     *
     * $mongo->find(array('mydb', 'users'), array('id' => 100));
     * $mongo->find('mydb.users', array('id' => 100));
     *
     * @param mixed $collection
     * @param array $query
     * @param array $fields
     * @access public
     * @return MongoCursor
     */
    public function find($collection, array $query = array(), array $fields = array()) {
        if (!is_array($collection)) $collection = explode('.', $collection);
        list($db, $collection) = $collection;

        return $this->selectCollection($db, $collection)->find($query, $fields);
    }

    private function parseCollection($collection) {
        return is_array($collection) ? $collection : explode('.', $collection);
    }

    /**
     * 查询collection
     * 返回第一条记录
     *
     * $mongo->findOne(array('mydb', 'users'), array('id' => 100));
     * $mongo->findOne('mydb.users', array('id' => 100));
     *
     * @param mixed $collection
     * @param array $query
     * @param array $fields
     * @access public
     * @return array
     */
    public function findOne($collection, array $query = array(), array $fields = array()) {
        list($db, $collection) = $this->parseCollection($collection);
        return $this->selectCollection($db, $collection)->findOne($query, $fields);
    }

    /**
     * 插入一条记录
     *
     * @param mixed $collection
     * @param array $record
     * @param array $options
     * @access public
     * @return mixed
     */
    public function insert($collection, array $record, array $options = array()) {
        list($db, $collection) = $this->parseCollection($collection);
        return $this->selectCollection($db, $collection)->insert($record, $options);
    }

    /**
     * 保存一条记录
     * 不存在则插入，存在则覆盖
     *
     * @param mixed $collection
     * @param array $record
     * @param array $options
     * @access public
     * @return mixed
     */
    public function save($collection, array $record, array $options = array()) {
        list($db, $collection) = $this->parseCollection($collection);
        return $this->selectCollection($db, $collection)->save($record, $options);
    }

    /**
     * 更新记录
     *
     * @param mixed $collection
     * @param array $criteria
     * @param array $new
     * @param array $options
     * @access public
     * @return boolean
     */
    public function update($collection, array $criteria, array $new, array $options = array()) {
        list($db, $collection) = $this->parseCollection($collection);
        return $this->selectCollection($db, $collection)->update($criteria, $new, $options);
    }

    /**
     * 删除记录
     *
     * @param mixed $collection
     * @param array $criteria
     * @param array $options
     * @access public
     * @return mixed
     */
    public function remove($collection, array $criteria, array $options = array()) {
        list($db, $collection) = $this->parseCollection($collection);
        return $this->selectCollection($db, $collection)->remove($criteria, $options);
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
