<?php
namespace Lysine\Db;

use Lysine\Config;
use Lysine\Db;
use Lysine\Db\Adapter;
use ArrayAccess;

/**
 * 多数据库连接切换类
 *
 * @package Db
 * @author yangyi <yangyi@surveypie.com>
 */
class Pool implements ArrayAccess {
    static protected $instance;

    static public $configPath = array('db', 'pool');

    protected $config = array();

    protected $dispatcher = array();

    protected $adapter = array();

    protected $default_node = '__default__';

    /**
     * 构造函数
     *
     * @param array $config
     * @access private
     * @return void
     */
    private function __construct() {
        if ($config = Config::get(self::$configPath)) $this->addNodes($config);
    }

    /**
     * 解构函数
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        $this->adapter = array();
    }

    /**
     * 设置默认节点名
     *
     * @param string $node_name
     * @access public
     * @return self
     */
    public function setDefaultNode($node_name) {
        $this->default_node = $node_name;
        return $this;
    }

    /**
     * 调用数据库连接
     *
     * @param string $fn
     * @param array $args
     * @access public
     * @return mixed
     */
    public function __call($fn, $args) {
        $adapter = $this->getAdapter();
        return call_user_func_array(array($adapter, $fn), $args);
    }

    /**
     * 获得数据库连接
     *
     * @param mixed $node_name
     * @access public
     * @return Lysine\Db\Adapter
     */
    public function getAdapter($node_name = null) {
        if ($node_name === null) $node_name = $this->default_node;

        if (!isset($this->adapter[$node_name])) {
            if (!isset($this->config[$node_name]))
                throw new \InvalidArgumentException('Adapter ['. $node_name .'] not found');

            $config = $this->config[$node_name];
            list($dsn, $user, $pass, $options) = Adapter::parseConfig($config);

            $this->adapter[$node_name] = Db::factory($dsn, $user, $pass, $options);
        }

        return $this->adapter[$node_name];
    }

    /**
     * 添加一个节点到列表里
     *
     * @param string $node_name
     * @param mixed $config
     * @access public
     * @return self
     */
    public function addNode($node_name, $config) {
        if ($config instanceof IAdapter) {
            $this->adapter[$node_name] = $config;
        } else {
            $this->config[$node_name] = $config;
        }
        return $this;
    }

    /**
     * 添加多个节点到列表里
     *
     * @param array $config_set
     * @access public
     * @return self
     */
    public function addNodes(array $config_set) {
        foreach ($config_set as $node_name => $config) $this->addNode($node_name, $config);
        return $this;
    }

    /**
     * 从列表里删除指定节点
     *
     * @param string $node_name
     * @access public
     * @return self
     */
    public function removeNode($node_name) {
        unset($this->config[$node_name], $this->adapter[$node_name]);
        return $this;
    }

    /**
     * 添加一个新的路由器
     *
     * @param string $group
     * @param callable $dispatcher
     * @access public
     * @return self
     */
    public function addDispatcher($group, $dispatcher) {
        if (!is_callable($dispatcher))
            throw new \UnexpectedValueException('Dispatcher is not callable');

        $this->dispatcher[$group] = $dispatcher;
        return $this;
    }

    /**
     * 使用指定的路由获得adapter
     *
     * @param string $group
     * @param mixed $token
     * @access public
     * @return Lysine\Db\IAdapter
     */
    public function dispatch($group, $token) {
        if (!isset($this->dispatcher[$group]))
            throw new \InvalidArgumentException('Group ['. $group .'] not found');

        $fn = $this->dispatcher[$group];
        $args = array_slice(func_get_args(), 1);
        $node_name = call_user_func_array($fn, $args);
        return $this->getAdapter($node_name);
    }

    /**
     * ArrayAccess接口方法
     *
     * @param string $node_name
     * @access public
     * @return boolean
     */
    public function offsetExists($node_name) {
        return array_key_exists($node_name, $this->config);
    }

    /**
     * ArrayAccess接口方法
     *
     * @param string $node_name
     * @access public
     * @return Lysine\Db\IAdapter
     */
    public function offsetGet($node_name) {
        return $this->getAdapter($node_name);
    }

    /**
     * ArrayAccess接口方法
     *
     * @param string $node_name
     * @param array $config
     * @access public
     * @return void
     */
    public function offsetSet($node_name, $config) {
        $this->addNode($node_name, $config);
    }

    /**
     * ArrayAccess接口方法
     *
     * @param string $node_name
     * @access public
     * @return void
     */
    public function offsetUnset($node_name) {
        $this->removeNode($node_name);
    }

    /**
     * 魔法方法
     *
     * @param string $node_name
     * @access public
     * @return self
     */
    public function __invoke($node_name = null) {
        return $this->getAdapter($node_name);
    }

    /**
     * 获得唯一实例
     *
     * @static
     * @access public
     * @return self
     */
    static public function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }
}
