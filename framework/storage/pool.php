<?php
namespace Lysine\Storage;

use Lysine\Config;
use Lysine\Utils\Singleton;

/**
 * 存储服务连接池
 *
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Pool extends Singleton {
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

    /**
     * 自定义路由方法
     * 路由方法调用后必须返回storage名称
     *
     * @param string $name
     * @param callable $fn
     * @access public
     * @return void
     */
    public function setDispatcher($name, $fn) {
        if (!is_callable($fn))
            throw new \UnexpectedValueException('Storage dispatcher is not callable');

        // 检查是否已经有这个名字的storage
        $path = self::$config_path;
        $path[] = $name;
        if ($config = Config::get($path))
            throw new \LogicException('Storage ['. $name .'] is exist, can not replace with dispatcher');

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
            $dispatcher_name = $name;
            $dispatcher = $this->dispatcher[$name];
            $args = array_slice(func_get_args(), 1);
            $name = call_user_func_array($dispatcher, $args);

            if ($name === null)
                throw new \LogicException('Storage dispatcher ['. $dispatcher_name .'] not return a storage name');
        }

        if (isset($this->storages[$name])) return $this->storages[$name];

        $path = self::$config_path;
        $path[] = $name;
        $config = Config::get($path);

        if (!$config)
            throw new \RuntimeException('Storage ['. $name .'] config not found');

        $class = $config['class'];
        $this->storages[$name] = new $class($config);

        return $this->storages[$name];
    }

    /**
     * 魔法方法
     * 等于调用get()方法
     *
     * @param string $name
     * @access public
     * @return Lsyine\IStorage
     */
    public function __invoke() {
        return call_user_func_array(array($this, 'get'), func_get_args());
    }
}
