<?php
namespace Lysine\Db;

use Lysine\Db;
use ArrayAccess;

/**
 * 多数据库连接切换类
 *
 * @package Db
 * @author yangyi <yangyi@surveypie.com>
 */
class Pool implements ArrayAccess {
    static protected $instance;

    protected $nodes = array();

    protected $dispatcher = array();

    protected $adapter = array();

    protected $default_node = '__default__';

    /**
     * 构造函数
     *
     * @param array $config
     * @access public
     * @return void
     */
    public function __construct(array $config = null) {
        if (!$config) $config = cfg('db', 'pool');
        if ($config) $this->addNodes($config);
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
            if (!isset($this->nodes[$node_name]))
                throw new \InvalidArgumentException('Adapter ['. $node_name .'] not found');

            list($dsn, $user, $pass, $options) = $this->nodes[$node_name];

            $this->adapter[$node_name] = Db::factory($dsn, $user, $pass, $options);
        }

        return $this->adapter[$node_name];
    }

    /**
     * 添加一个节点到列表里
     *
     * @param string $node_name
     * @param array $config
     * @access public
     * @return self
     */
    public function addNode($node_name, array $config) {
        $this->nodes[$node_name] = Db::parseConfig($config);
        return $this;
    }

    /**
     * 添加多个节点到列表里
     *
     * @param array $nodes
     * @access public
     * @return self
     */
    public function addNodes(array $nodes) {
        foreach ($nodes as $node_name => $config) $this->addNode($node_name, $config);
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
        unset($this->nodes[$node_name], $this->adapter[$node_name]);
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
     * @return Lysine\Db\Adapter
     */
    public function dispatch($group, $token) {
        if (!isset($this->dispatcher[$group]))
            throw new \InvalidArgumentException('Group ['. $group .'] not found');

        $fn = $this->dispatcher[$group];
        $node_name = call_user_func($fn, $token);
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
        return array_key_exists($node_name, $this->nodes);
    }

    /**
     * ArrayAccess接口方法
     *
     * @param string $node_name
     * @access public
     * @return Lysine\Db\Adapter
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
