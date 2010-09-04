<?php
namespace Lysine\Storage;

abstract class Cache {
    protected $prefix;

    /**
     * 保存缓存
     *
     * @param string $key
     * @param mixed $val
     * @param integer $life_time
     * @access public
     * @return boolean
     */
    abstract public function set($key, $val, $life_time = null);

    /**
     * 批量保存
     *
     * @param array $data
     * @param mixed $life_time
     * @access public
     * @return boolean
     */
    abstract public function mset(array $data, $life_time = null);

    /**
     * 读取缓存
     *
     * @param string $key
     * @access public
     * @return mixed
     */
    abstract public function get($key);

    /**
     * 批量读取
     *
     * @param array $keys
     * @access public
     * @return array
     */
    abstract public function mget(array $keys);

    /**
     * 删除缓存
     *
     * @param string $key
     * @access public
     * @return boolean
     */
    abstract public function delete($key);

    /**
     * 批量删除
     *
     * @param array $key
     * @access public
     * @return boolean
     */
    abstract public function mdelete(array $key);

    public function __construct(array $config) {
        foreach ($config as $prop => $val)
            $this->$prop = $val;
    }

    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }

    protected function makeKey($key) {
        if (is_array($key)) $key = implode('.', $key);

        return md5("{$this->prefix}.{$key}");
    }
}
