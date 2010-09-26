<?php
/**
 * 事件触发
 *
 * @package Utils
 * @author yangyi <yangyi@surveypie.com>
 */
namespace Lysine\Utils;

use Lysine\Error;
use Lysine\Utils\Singleton;

class Events extends Singleton {
    /**
     * 事件列表
     *
     * @var array
     * @access protected
     */
    private $events = array();

    /**
     * 生成对象的唯一标示
     *
     * @param object $target
     * @access protected
     * @return string
     */
    final protected function keyOf($target) {
        if (!is_object($target))
            throw Error::invalid_argument('keyOf', __CLASS__);
        return spl_object_hash($target);
    }

    /**
     * 监听事件
     *
     * @param object $target
     * @param string $name
     * @param callable $callback
     * @access public
     * @return void
     */
    final public function addEvent($target, $name, $callback) {
        if (!is_callable($callback))
            throw Error::not_callable('Events::addEvent() parameter 3');

        $key = $this->keyOf($target);
        if (!isset($this->events[$key][$name])) $this->events[$key][$name] = array();

        $this->events[$key][$name][] = $callback;
    }

    /**
     * 触发事件
     *
     * @param object $target
     * @param string $name
     * @param mixed $args
     * @access public
     * @return void
     */
    final public function fireEvent($target, $name, array $args = array()) {
        $key = $this->keyOf($target);

        if (!isset($this->events[$key][$name])) return;

        foreach ($this->events[$key][$name] as $callback)
            call_user_func_array($callback, $args);
    }

    /**
     * 取消监听
     *
     * @param object $target
     * @param string $name
     * @access public
     * @return void
     */
    final public function clearEvent($target, $name = null) {
        $key = $this->keyOf($target);

        if ($name) {
            unset($this->events[$key][$name]);
        } else {
            unset($this->events[$key]);
        }
    }
}
