<?php
namespace Lysine\Storage;

use Lysine\Config;
use Lysine\StorageError;
use Lysine\Utils\Singleton;

/**
 * 存储服务连接池
 *
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Pool extends Singleton {
    // 生成存储服务实例之前
    const BEFORE_CREATE_INSTANCE_EVENT = 'before create instance event';
    // 生成存储服务实例之后
    const AFTER_CREATE_INSTANCE_EVENT = 'after create instance event';

    /**
     * 存储服务配置路径
     */
    static public $config_path = array('storages');

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
     * 获得指定名字的存储服务配置信息
     *
     * @param string $name
     * @access public
     * @return array
     */
    public function getConfig($name) {
        $path = self::$config_path;
        $path[] = $name;
        $config = Config::get($path);

        if (!$config)
            throw StorageError::undefined_storage($name);

        if (isset($config['__IMPORT__'])) {
            $config = array_merge($this->getConfig($config['__IMPORT__']), $config);
            unset($config['__IMPORT__']);
        }
        return $config;
    }

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
            throw StorageError::not_callable("Storage dispatcher ${name}");

        // 检查是否已经有这个名字的storage
        $path = self::$config_path;
        $path[] = $name;
        if ($config = Config::get($path))
            throw new StorageError('Storage ['. $name .'] is exist, can not replace with dispatcher');

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
                throw new StorageError('Storage dispatcher ['. $dispatcher_name .'] not return a storage name');
        }

        if (isset($this->storages[$name])) return $this->storages[$name];

        $config = $this->getConfig($name);

        fire_event($this, self::BEFORE_CREATE_INSTANCE_EVENT, array($name, $config));

        $class = $config['class'];
        unset($config['class']);

        $instance = new $class($config);

        fire_event($this, self::AFTER_CREATE_INSTANCE_EVENT, array($instance, $name, $config));

        return $this->storages[$name] = $instance;
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
