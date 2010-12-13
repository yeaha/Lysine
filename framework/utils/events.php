<?php
namespace Lysine\Utils;

use Lysine\Error;
use Lysine\Utils\Singleton;

/**
 * 对象事件机制封装
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
     * 监听对象某个事件
     *
     * @param object $obj
     * @param string $event
     * @param callback $callback
     * @access public
     * @return void
     */
    public function listen($obj, $event, $callback) {
        if (!is_callable($callback))
            throw Error::not_callable('Events::listen() parameter 3');

        $key = $this->keyOf($obj);
        $this->listen[$key][$event][] = $callback;
    }

    /**
     * 订阅类的事件
     *
     * @param mixed $class
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
     * 触发对象事件
     *
     * @param object $obj
     * @param string $event
     * @param array $args
     * @access public
     * @return integer 事件回调次数
     */
    public function fire($obj, $event, array $args = array()) {
        $fire = 0;  // 回调次数
        if (!$this->listen && !$this->subscribe) return $fire;

        $key = $this->keyOf($obj);

        if (isset($this->listen[$key][$event])) {
            foreach ($this->listen[$key][$event] as $callback) {
                call_user_func_array($callback, $args);
                $fire++;
            }
        }

        if (!$this->subscribe) return $fire;

        // 订阅回调参数
        // 第一个参数是事件发起对象
        // 第二个参数是事件参数
        $args = array($obj, $args);
        $class = strtolower(get_class($obj));

        if (isset($this->subscribe[$class][$event])) {
            foreach ($this->subscribe[$class][$event] as $callback) {
                call_user_func_array($callback, $args);
                $fire++;
            }
        }

        return $fire;
    }

    /**
     * 取消对象事件监听
     *
     * @param object $obj
     * @param string $event
     * @access public
     * @return void
     */
    public function clear($obj, $event = null) {
        $key = $this->keyOf($obj);

        if ($event) {
            unset($this->listen[$key][$event]);
        } else {
            unset($this->listen[$key]);
        }
    }
}
