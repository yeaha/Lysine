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

    public function __construct($element) {
        if (is_array($element)) {
            $this->coll = $element;
        } else {
            $this->coll = array($element);
        }
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

    public function shift() {
        return array_shift($this->coll);
    }

    public function unshift($element) {
        array_unshift($this->coll, $element);
        return $this;
    }

    public function pop() {
        return array_pop($this->coll);
    }

    public function push($element) {
        array_push($this->coll, $element);
        return $this;
    }

    /**
     * 把每个元素作为参数传递给callback
     * 把所有的返回值以Ly_Coll方式返回
     *
     * @param mixed $callback
     * @param array $pre_args
     * @param array $post_args
     * @access public
     * @return Ly_Coll
     */
    public function map($callback, array $pre_args = null, array $post_args = null) {
        $map = array();
        foreach ($this->coll as $key => $el) {
            $args = array($el);
            if ($pre_args) $args = array_merge($pre_args, $args);
            if ($post_args) $args = array_merge($args, $post_args);

            $map[$key] = call_user_func_array($callback, $args);
        }

        return new self($map);
    }

    /**
     * 把每个元素作为参数传递给callback
     * 和map不同，map会创建一个新的Ly_Coll
     * each是会修改自身
     *
     * @param mixed $callback
     * @param array $pre_args
     * @param array $post_args
     * @access public
     * @return Ly_Coll
     */
    public function each($callback, array $pre_args = null, array $post_args = null) {
        foreach ($this->coll as $key => &$el) {
            $args = array($el);
            if ($pre_args) $args = array_merge($pre_args, $args);
            if ($post_args) $args = array_merge($args, $post_args);

            $el = call_user_func_array($callback, $args);
        }

        return $this;
    }

    /**
     * 把数组中的每个元素作为参数传递给callback
     * 保留callback返回true的值
     *
     * @param mixed $callback
     * @access public
     * @return Ly_Coll
     */
    public function filter($callback, $create_new = true) {
        $filter = array();
        foreach ($this->coll as $key => $el) {
            $test = call_user_func($callback, $el);

            if ($create_new AND $test) {
                $filter[$key] = $el;
                continue;
            }

            if (!$test) unset($this->coll[$key]);
        }
        return $create_new ? new self($filter) : $this;
    }

    /**
     * 调用每个元素的方法
     * 把每次调用的结果以Ly_Coll类型返回
     *
     * @param string $fn
     * @param array $args
     * @access public
     * @return Ly_Coll
     */
    public function call($fn, array $args = null) {
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
}
