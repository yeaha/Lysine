<?php
/**
 * Memcache缓存
 * 使用pecl-memcached
 *
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
class Ly_Cache_Memcached implements Ly_Cache_Interface {
    protected $conn;
    protected $default_life_time;

    /**
     * 构造函数
     * array(
     *     'default_life_time' => 0,
     *     'servers' => array(
     *         array('192.168.1.100', 11211, 60),
     *         array('192.168.1.101', 11211, 40),
     *     ),
     *     'options' => array(
     *         Memcached::OPT_PREFIX_KEY => 'app id',
     *         Memcached::OPT_SERIALIZER => Memcached::OPT_SERIALIZER_JSON,
     *         Memcached::OPT_HASH => Memcache::HASH_MD5,
     *         Memcached::OPT_DISTRIBUTION => Memcache::DISTRIBUTION_CONSISTENT,
     *     )
     * )
     * @param array $options
     * @access public
     * @return void
     */
    public function __construct(array $config = null) {
        if (!extension_loaded('memcached'))
            throw new RuntimeException('Memcached extension must be loaded before use.');

        $conn = new Memcached();

        if (!$config) $config = array();
        $options = isset($config['options']) ? $config['options'] : array();
        $servers = isset($config['servers']) ? $config['servers'] : array(array('127.0.0.1', 11211));
        $this->default_life_time = isset($config['default_life_time']) ? $config['default_life_time'] : 0;

        foreach ($options as $opt => $val) $conn->setOptions($opt, $val);
        $conn->addServers($servers);

        $this->conn = $conn;
    }

    public function __call($fn, $args) {
        return call_user_func_array(array($this->conn, $fn), $args);
    }

    /**
     * 保存缓存数据
     *
     * @param string $key
     * @param mixed $val
     * @param integer $life_time
     * @access public
     * @return boolean
     */
    public function set($key, $val, $life_time = null) {
        if (!$life_time) $life_time = $this->default_life_time;
        $expire_time = $life_time ? (now() + $life_time) : 0;
        return $this->conn->set($key, $val, $expire_time);
    }

    /**
     * 获得缓存数据
     *
     * @param string $key
     * @param callback $callback
     * @access public
     * @return mixed
     */
    public function get($key) {
        return $this->conn->get($key);
    }

    /**
     * 删除缓存数据
     *
     * @param mixed $key
     * @access public
     * @return boolean
     */
    public function delete($key) {
        $args = func_get_args();
        return call_user_func_array(array($this->conn, 'delete'), $args);
    }
}
