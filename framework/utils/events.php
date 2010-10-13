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
     * 订阅类的某个或者所有事件
     *
     * [code]
     * // 订阅Topic类的所有事件
     * $event->subscribe('Topic', $callback);
     * // 订阅Topic类的delete事件
     * $event->subscribe(array('Topic', 'delete'), $callback);
     * [/code]
     *
     * @param mixed $class
     * @param callback $callback
     * @access public
     * @return void
     */
    public function subscribe($class, $callback) {
        if (!is_callable($callback))
            throw Error::not_callable('Events::subscribe() parameter 2');

        $event = '*';
        if (is_array($class))
            list($class, $event) = $class;

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
        $key = $this->keyOf($obj);
        $fire = 0;  // 回调次数

        if (isset($this->listen[$key][$event])) {
            foreach ($this->listen[$key][$event] as $callback)
                call_user_func_array($callback, $args);
            $fire += count($this->listen[$key][$event]);
        }

        // 订阅回调参数
        // 第一个参数是事件发起对象
        // 第二个参数是事件类型
        // 第三个参数是事件参数
        $args = array($obj, $event, $args);

        $class = get_class($obj);
        if (isset($this->subscribe[$class][$event]) || isset($this->subscribe[$class]['*'])) {
            foreach ($this->subscribe[$class] as $sevent => $callback_set) {
                if ($sevent != '*' && $sevent != $event) continue;
                foreach ($callback_set as $callback)
                    call_user_func_array($callback, $args);
                $fire += count($callback_set);
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
