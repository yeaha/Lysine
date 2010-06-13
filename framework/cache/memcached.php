<?php
/**
 * Memcache缓存
 * 使用pecl-memcached
 *
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
class Ly_Cache_Memcached implements Ly_Cache_Interface {
    /**
     * 服务器连接
     *
     * @var mixed
     * @access protected
     */
    protected $conn;

    public function __construct(array $policy = null) {
    }

    /**
     * 除了get() set() delete()之外
     * 其他的调用全部直接转发给后端连接对象
     *
     * @param string $fn
     * @param array $args
     * @access public
     * @return mixed
     */
    public function __call($fn, $args) {
        if (!$this->conn) return false;

        if (method_exists($this->conn, $fn))
            return call_user_func_array(array($this->conn, $fn), $args);

        throw new BadMethodCallException('Ly_Cache_Memcached: call bad method '. $fn);
    }

    /**
     * 保存缓存数据
     *
     * @param mixed $key
     * @param mixed $val
     * @param integer $life_time
     * @access public
     * @return boolean
     */
    public function set($key, $val, $life_time = null) {
    }

    /**
     * 获得缓存数据
     *
     * @param mixed $key
     * @access public
     * @return mixed
     */
    public function get($key) {
    }

    /**
     * 删除缓存数据
     *
     * @param mixed $key
     * @access public
     * @return boolean
     */
    public function delete($key) {
    }
}
