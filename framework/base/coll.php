<?php
class Ly_Coll implements Iterator, Countable, ArrayAccess {
    protected $coll;

    /**
     * Interator接口
     *
     * @var mixed
     * @access protected
     */
    protected $has_next = false;

    public function __construct(array $elements = array()) {
        $this->coll = $elements;
    }

    /**
     * Iterator接口
     *
     * @access public
     * @return miexed
     */
    public function current() {
        return current($this->coll);
    }

    /**
     * Iterator接口
     *
     * @access public
     * @return mixed
     */
    public function key() {
        return key($this->coll);
    }

    /**
     * Iterator接口
     *
     * @access public
     * @return void
     */
    public function next() {
        $this->has_next = (next($this->coll) !== false);
    }

    /**
     * Iterator接口
     *
     * @access public
     * @return void
     */
    public function rewind() {
        $this->has_next = (reset($this->coll) !== false);
    }

    /**
     * Iterator接口
     *
     * @access public
     * @return void
     */
    public function valid() {
        return $this->has_next;
    }

    /**
     * Countable接口
     *
     * @access public
     * @return integer
     */
    public function count() {
        return count($this->coll);
    }

    /**
     * ArrayAccess接口
     *
     * @param mixed $offset
     * @access public
     * @return boolean
     */
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->coll);
    }

    /**
     * ArrayAccess接口
     *
     * @param mixed $offset
     * @access public
     * @return mixed
     */
    public function offsetGet($offset) {
        return array_key_exists($offset, $this->coll)
             ? $this->coll[$offset]
             : false;
    }

    /**
     * ArrayAccess接口
     *
     * @param mixed $offset
     * @param mixed $val
     * @access public
     * @return void
     */
    public function offsetSet($offset, $val) {
        $this->coll[$offset] = $val;
    }

    /**
     * ArrayAccess接口
     *
     * @param mixed $offset
     * @access public
     * @return void
     */
    public function offsetUnset($offset) {
        unset($this->coll[$offset]);
    }

    /**
     * 返回并删除第一个元素
     *
     * @access public
     * @return mixed
     */
    public function shift() {
        return array_shift($this->coll);
    }

    /**
     * 把元素插入到数组第一位
     *
     * @param mixed $element
     * @access public
     * @return Ly_Coll
     */
    public function unshift($element) {
        $args = array_reverse(func_get_args());
        foreach ($args as $arg) array_unshift($this->coll, $arg);
    }

    /**
     * 返回并删除最后一个元素
     *
     * @access public
     * @return mixed
     */
    public function pop() {
        return array_pop($this->coll);
    }

    /**
     * 把元素插入到数组尾部
     *
     * @param mixed $element
     * @access public
     * @return Ly_Coll
     */
    public function push($element) {
        $args = func_get_args();
        foreach ($args as $arg) array_push($this->coll, $arg);
    }

    /**
     * 还原为数组
     *
     * @access public
     * @return array
     */
    public function toArray() {
        return $this->coll;
    }

    /**
     * 把每个元素作为参数传递给callback
     * 把所有的返回值以Ly_Coll方式返回
     *
     * @param callback $callback
     * @param mixed $pre_args
     * @param mixed $post_args
     * @access public
     * @return Ly_Coll
     */
    public function map($callback, $pre_args = null, $post_args = null) {
        $map = array();
        if (is_null($pre_args)) $pre_args = array();
        if (!is_array($pre_args)) $pre_args = array($pre_args);
        if (is_null($post_args)) $post_args = array();
        if (!is_array($post_args)) $post_args = array($post_args);

        foreach ($this->coll as $key => $el) {
            $args = array_merge($pre_args, array($el), $post_args);
            $map[$key] = call_user_func_array($callback, $args);
        }

        return new self($map);
    }

    /**
     * 把每个元素作为参数传递给callback
     * 和map不同，map会创建一个新的Ly_Coll
     * each是会修改自身
     *
     * @param callback $callback
     * @param mixed $pre_args
     * @param mixed $post_args
     * @access public
     * @return Ly_Coll
     */
    public function each($callback, $pre_args = null, $post_args = null) {
        if (is_null($pre_args)) $pre_args = array();
        if (!is_array($pre_args)) $pre_args = array($pre_args);
        if (is_null($post_args)) $post_args = array();
        if (!is_array($post_args)) $post_args = array($post_args);

        foreach ($this->coll as $key => &$el) {
            $args = array_merge($pre_args, array($el), $post_args);
            $el = call_user_func_array($callback, $args);
        }

        return $this;
    }

    /**
     * 把数组中的每个元素作为参数传递给callback
     * 找出符合条件的值
     *
     * @param callback $callback
     * @param mixed $pre_args
     * @param mixed $post_args
     * @access public
     * @return Ly_Coll
     */
    public function find($callback, $pre_args = null, $post_args = null) {
        $find = array();
        if (is_null($pre_args)) $pre_args = array();
        if (!is_array($pre_args)) $pre_args = array($pre_args);
        if (is_null($post_args)) $post_args = array();
        if (!is_array($post_args)) $post_args = array($post_args);

        foreach ($this->coll as $key => $el) {
            $args = array_merge($pre_args, array($el), $post_args);
            if (call_user_func_array($callback, $args)) $find[$key] = $el;
        }
        return new self($find);
    }

    /**
     * 把数组中的每个元素作为参数传递给callback
     * 过滤掉不符合条件的值
     *
     * @param callback $callback
     * @param mixed $pre_args
     * @param mixed $post_args
     * @access public
     * @return Ly_Coll
     */
    public function filter($callback, $pre_args = null, $post_args = null) {
        if (is_null($pre_args)) $pre_args = array();
        if (!is_array($pre_args)) $pre_args = array($pre_args);
        if (is_null($post_args)) $post_args = array();
        if (!is_array($post_args)) $post_args = array($post_args);

        foreach ($this->coll as $key => $el) {
            $args = array_merge($pre_args, array($el), $post_args);
            if (!call_user_func_array($callback, $args))
                unset($this->coll[$key]);
        }
        return $this;
    }

    /**
     * 调用每个元素的方法
     * 把每次调用的结果以Ly_Coll类型返回
     *
     * @param string $fn
     * @param mixed $args
     * @access public
     * @return Ly_Coll
     */
    public function call($fn, $args = null) {
        $args = array_slice(func_get_args(), 1);

        $result = array();
        foreach ($this->coll as $key => $el) {
            if (is_callable(array($el, $fn))) {
                $result[$key] = call_user_func_array(array($el, $fn), $args);
            } else {
                $result[$key] = false;
            }
        }
        return new self($result);
    }

    /**
     * 把所有的元素实例化为指定的类
     * 指定的类必须有invoke静态方法
     * 主要是5.2x不支持__invoke()方法，否则不用这么麻烦
     *
     * @param string $class
     * @param mixed $pre_args
     * @param mixed $post_args
     * @access public
     * @return Ly_Coll
     */
    public function package($class, $pre_args = null, $post_args = null) {
        if (!class_exists($class))
            throw new RuntimeException("Package class {$class} not exist!");

        if (!method_exists($class, 'invoke'))
            throw new BadMethodCallException("{$class} must have static method 'invoke'!");

        $callback = array($class, 'invoke');

        return $this->each($callback, $pre_args, $post_args);
    }
}
