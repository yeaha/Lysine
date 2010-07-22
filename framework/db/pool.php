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

    protected $config;

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
        $this->config = $config ? $config : cfg('db', 'pool');
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
    public function use($name) {
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
        if (!$name) $name = $this->current;

        if (!isset($this->adapter[$name])) {
            if (!isset($this->config[$name]))
                throw new \RuntimeException();

            $config = $this->config[$name];
            if (!isset($config['dsn']))
                throw new \RuntimeException();

            $user = isset($config['user']) ? $config['user'] : null;
            $pass = isset($config['pass']) ? $config['pass'] : null;
            $option = (isset($config['options']) && is_array($config['options']))
                    ? $config['options']
                    : array();

            $this->adapter[$name] = Db::factory($config['dsn'], $user, $pass, $options);
        }

        return $this->adapter[$name];
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
