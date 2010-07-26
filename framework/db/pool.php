<?php
namespace Lysine\Db;

use Lysine\Db;

/**
 * 多数据库连接切换类
 *
 * @package Db
 * @author yangyi <yangyi@surveypie.com>
 */
class Pool {
    static protected $instance;

    protected $servers = array();

    protected $dispatcher = array();

    protected $adapter = array();

    protected $current = '__default__';

    /**
     * 构造函数
     *
     * @param array $config
     * @access public
     * @return void
     */
    public function __construct(array $config = null) {
        if (!$config) $config = cfg('db', 'pool');
        if ($config) $this->addServers($config);
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
     * 切换数据库连接
     *
     * @param string $name
     * @access public
     * @return self
     */
    public function useDb($name) {
        $this->current = $name;
        return $this;
    }

    /**
     * 获得数据库连接
     *
     * @param mixed $name
     * @access public
     * @return Lysine\Db\Adapter
     */
    public function getAdapter($name = null) {
        if ($name === null) $name = $this->current;

        if (!isset($this->adapter[$name])) {
            if (!isset($this->servers[$name]))
                throw new \InvalidArgumentException('Adapter ['. $name .'] not found');

            list($dsn, $user, $pass, $options) = $this->servers[$name];

            $this->adapter[$name] = Db::factory($dsn, $user, $pass, $options);
        }

        return $this->adapter[$name];
    }

    /**
     * 添加一个server到列表里
     *
     * @param mixed $string
     * @param array $config
     * @access public
     * @return self
     */
    public function addServer($name, array $config) {
        $this->server[$name] = Db::parseConfig($config);
        return $this;
    }

    /**
     * 添加多个server到列表里
     *
     * @param array $servers
     * @access public
     * @return self
     */
    public function addServers(array $servers) {
        foreach ($servers as $name => $config) $this->addServer($name, $config);
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
        $name = call_user_func($fn, $token);
        return $this->getAdapter($name);
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
