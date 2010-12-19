<?php
namespace Lysine\Utils;

use Lysine\Error;
use Lysine\Utils\Singleton;

/**
 * 事件机制封装
 *
 * @uses Singleton
 * @package Utils
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Events extends Singleton {
    /**
     * 事件监听列表
     *
     * @var array
     * @access private
     */
    private $listen = array();

    /**
     * 事件订阅列表
     *
     * @var array
     * @access private
     */
    private $subscribe = array();

    /**
     * 得到对象唯一标示
     *
     * @param object $obj
     * @access private
     * @return string
     */
    private function keyOf($obj) {
        if (!is_object($obj))
            throw Error::invalid_argument('keyOf', __CLASS__);
        return spl_object_hash($obj);
    }

    /**
     * 监听某个事件
     *
     * @param mixed $source
     * @param string $event
     * @param callback $callback
     * @access public
     * @return void
     */
    public function listen($source, $event, $callback) {
        if (!is_callable($callback))
            throw Error::not_callable('Events::listen() parameter 3');

        $source = is_object($source) ? $this->keyOf($source) : strtolower($source);
        $this->listen[$source][$event][] = $callback;
    }

    /**
     * 订阅类的事件
     *
     * @param string $class
     * @param string $event
     * @param callback $callback
     * @access public
     * @return void
     */
    public function subscribe($class, $event, $callback) {
        if (!is_callable($callback))
            throw Error::not_callable('Events::subscribe() parameter 3');

        $class = strtolower(ltrim($class, '\\'));
        $this->subscribe[$class][$event][] = $callback;
    }

    /**
     * 触发事件
     *
     * @param mixed $source
     * @param string $event
     * @param array $args
     * @access public
     * @return integer 事件回调次数
     */
    public function fire($source, $event, array $args = array()) {
        $fire = 0;  // 回调次数
        if (!$this->listen && !$this->subscribe) return $fire;

        $key = is_object($source) ? $this->keyOf($source) : strtolower($source);
        if (isset($this->listen[$key][$event])) {
            foreach ($this->listen[$key][$event] as $callback) {
                call_user_func_array($callback, $args);
                $fire++;
            }
        }

        // 订阅事件仅适用于对象
        if (!$this->subscribe || !is_object($source)) return $fire;

        $class = strtolower(get_class($source));
        if (!isset($this->subscribe[$class][$event])) return $fire;

        // 订阅回调参数
        // 第一个参数是事件对象
        // 第二个参数是事件参数
        $args = array($source, $args);
        foreach ($this->subscribe[$class][$event] as $callback) {
            call_user_func_array($callback, $args);
            $fire++;
        }

        return $fire;
    }

    /**
     * 取消事件监听
     *
     * @param mixed $source
     * @param string $event
     * @access public
     * @return void
     */
    public function clear($source, $event = null) {
        $source = is_object($source) ? $this->keyOf($source) : strtolower($source);

        if ($event === null) {
            unset($this->listen[$source]);
        } else {
            unset($this->listen[$source][$event]);
        }
    }
}
