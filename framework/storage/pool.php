<?php
namespace Lysine\Storage;

use Lysine\Config;

/**
 * 存储服务连接池
 *
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Pool {
    /**
     * 唯一实例
     */
    static private $instance;

    /**
     * 存储服务配置路径
     */
    static public $config_path = array('storage', 'pool');

    /**
     * 默认存储器名字
     */
    static public $default_storage = '__default__';

    /**
     * 存储服务连接实例列表
     *
     * @var array
     * @access private
     */
    private $storages = array();

    /**
     * 自定义路由方法
     *
     * @var array
     * @access private
     */
    private $dispatcher = array();

    private function __construct() {
    }

    /**
     * 获得唯一实例
     *
     * @static
     * @access public
     * @return Lysine\Storage\Pool
     */
    static public function instance() {
        if (!self::$instance) self::$instance = new self;
        return self::$instance;
    }

    /**
     * 自定义路由方法
     *
     * @param string $name
     * @param callable $fn
     * @access public
     * @return void
     */
    public function setDispatcher($name, $fn) {
        $this->dispatcher[$name] = $fn;
    }

    /**
     * 根据存储器名字或者自定义路由方法获得存储服务连接实例
     *
     * @param string $name
     * @access public
     * @return Lysine\IStorage
     */
    public function get($name = null) {
        if ($name === null) $name = self::$default_storage;

        if (isset($this->dispatcher[$name])) {
            $dispatcher = $this->dispatcher[$name];
            $args = array_slice(func_get_args(), 1);
            $name = call_user_func_array($dispatcher, $args);
        }

        if (!isset($this->storages[$name])) {
            $path = self::$config_path;
            $path[] = $name;
            $config = Config::get($path);

            $class = $config['class'];
            $this->storages[$name] = new $class($config);
        }

        return $this->storages[$name];
    }
}
