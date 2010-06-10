<?php
/**
 * 事件逻辑封装
 *
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
class Ly_Events {
    protected $events = array();

    /**
     * 监听事件
     *
     * @param string $name
     * @param callback $callback
     * @access public
     * @return self
     */
    public function addEvent($name, $callback) {
        if (!is_callable($callback))
            throw new BadFunctionCallException('Bad event callback');

        if (!array_key_exists($name, $this->events))
            $this->events[$name] = array();

        $this->events[$name][] = $callback;

        return $this;
    }

    /**
     * 触发事件
     *
     * @param string $name
     * @param mixed $args
     * @access public
     * @return boolean
     */
    public function fireEvent($name, $args = null) {
        if (!array_key_exists($name, $this->events)) return false;

        $args = is_array($args) ? $args : array_slice(func_get_args(), 1);
        foreach ($this->events[$name] as $callback)
            call_user_func_array($callback, $args);

        return true;
    }

    /**
     * 取消监听
     *
     * @param string $name
     * @param callback $callback
     * @access public
     * @return boolean
     */
    public function removeEvent($name, $callback) {
        if (!array_key_exists($name, $this->events)) return false;

        foreach ($this->events[$name] as $key => $fn) {
            if ($fn === $callback) {
                unset($this->events[$name][$key]);
                return true;
            }
        }

        return false;
    }
}
