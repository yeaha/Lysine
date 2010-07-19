<?php
namespace Lysine\Utils;

/**
 * 事件触发
 *
 * @package Utils
 * @author yangyi <yangyi@surveypie.com>
 */
class Events {
    static protected $instance;

    /**
     * 事件列表
     *
     * @var array
     * @access protected
     */
    protected $events = array();

    /**
     * 获得单一实例
     *
     * @static
     * @access public
     * @return Events
     */
    static public function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    /**
     * 生成对象的唯一标示
     *
     * @param object $target
     * @access protected
     * @return string
     */
    protected function keyOf($target) {
        return get_class($target) .'#'. spl_object_hash($target);
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
    public function addEvent($target, $name, $callback) {
        if (!is_callable($callback))
            throw new BadFunctionCallException('Bad event callback');

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
    public function fireEvent($target, $name, $args = null) {
        $key = $this->keyOf($target);

        if (isset($this->events[$key][$name])) {
            $args = is_array($args) ? $args : array_slice(func_get_args(), 2);
            foreach ($this->events[$key][$name] as $callback)
                call_user_func_array($callback, $args);
        }
    }

    /**
     * 取消监听
     *
     * @param object $target
     * @param string $name
     * @access public
     * @return void
     */
    public function clearEvent($target, $name = null) {
        $key = $this->keyOf($target);

        if ($name) {
            unset($this->events[$key][$name]);
        } else {
            unset($this->events[$key]);
        }
    }

    /**
     * 魔法方法，函数风格调用
     *
     * $event = Events::instance();
     * $event($target, 'event name');
     *
     * @access public
     * @return void
     */
    public function __invoke() {
        $args = func_get_args();
        call_user_func_array(array($this, 'fireEvent'), $args);
    }
}
