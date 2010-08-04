<?php
namespace Lysine\Utils;

class Coll implements \ArrayAccess, \Countable, \IteratorAggregate {
    /**
     * collection元素数组
     *
     * @var mixed
     * @access array
     */
    protected $coll;

    /**
     * 构造函数
     *
     * @param array $elements
     * @access public
     * @return void
     */
    public function __construct(array $elements = array()) {
        $this->coll = $elements;
    }

    /**
     * IteratorAggregate接口
     * 返回迭代子
     *
     * @access public
     * @return ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->coll);
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
     * 获得第一个元素
     *
     * @access public
     * @return mixed
     */
    public function first() {
        return reset($this->coll);
    }

    /**
     * 获得最后一个元素
     *
     * @access public
     * @return mixed
     */
    public function last() {
        return end($this->coll);
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
     * @return Lysine\Utils\Coll
     */
    public function unshift($element) {
        $args = func_get_args();
        array_splice($this->coll, 0, 0, $args);
        return $this;
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
     * @return Lysine\Utils\Coll
     */
    public function push($element) {
        $args = func_get_args();
        array_splice($this->coll, count($this->coll), 0, $args);
        return $this;
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
     * 把所有的返回值以Lysine\Utils\Coll方式返回
     * 返回新的collection
     *
     * @param callback $callback
     * @param mixed $more
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function map($callback, $more = null) {
        $args = func_get_args();

        if (count($args) > 1) {
            if (is_array($args[1])) {
                $more = $args[1];
            } else {
                $more = array_slice($args, 1);
            }

            return new self(array_map($callback, $this->coll, $more));
        } else {
            return new self(array_map($callback, $this->coll));
        }
    }

    /**
     * 把每个元素作为参数传递给callback
     * 和map不同，map会创建一个新的Lysine\Utils\Coll
     * each会修改自身
     *
     * @param callback $callback
     * @param mixed $more
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function each($callback, $more = null) {
        $more = is_array($more) ? $more : array_slice(func_get_args(), 1);

        foreach ($this->coll as $key => $val) {
            $args = array($val, $key);
            if ($more) $args = array_merge($args, $more);

            $this->coll[$key] = call_user_func_array($callback, $args);
        }
        return $this;
    }

    /**
     * 把数组中的每个元素作为参数传递给callback
     * 找出符合条件的值
     * 返回新的collection
     *
     * @param callback $callback
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function find($callback) {
        $find = array();

        foreach ($this->coll as $key => $el) {
            if (call_user_func($callback, $el)) $find[$key] = $el;
        }
        return new self($find);
    }

    /**
     * 把数组中的每个元素作为参数传递给callback
     * 过滤掉不符合条件的值
     * 修改当前collection
     *
     * @param callback $callback
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function filter($callback) {
        foreach ($this->coll as $key => $el) {
            if (!call_user_func($callback, $el))
                unset($this->coll[$key]);
        }
        return $this;
    }

    /**
     * 调用每个元素的方法
     * 把每次调用的结果以Lysine\Utils\Coll类型返回
     * 返回新的collection
     *
     * @param string $fn
     * @param mixed $args
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function call($fn, $args = null) {
        if (!is_array($args)) {
            $args = func_get_args();
            $args = array_slice($args, 1);
        }

        $result = array();
        foreach ($this->coll as $key => $el) {
            $result[$key] = call_user_func_array(array($el, $fn), $args);
        }
        return new self($result);
    }

    /**
     * 魔法方法
     * 依次调用每个元素的方法
     *
     * @param string $fn
     * @param array $args
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function __call($fn, $args) {
        return $this->call($fn, $args);
    }

    /**
     * 魔法方法
     * 依次获取每个元素的属性
     *
     * @param string $k
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function __get($k) {
        $result = array();
        foreach ($this->coll as $key => $el) $result[$key] = $el->$k;
        return new self($result);
    }

    /**
     * array_slice方法
     *
     * @param integer $offset
     * @param integer $length
     * @param boolean $preserve_keys
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function slice($offset, $length = null, $preserve_keys = false) {
        return new self(array_slice($this->coll, $offset, $length, $preserve_keys));
    }

    /**
     * array_splice方法
     *
     * @param integer $offset
     * @param integer $length
     * @param mixed $replace
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function splice($offset, $length = 0, $replace = null) {
        $args = func_get_args();
        if (count($args) > 2) {
            $replace = $args[2];
            return new self(array_splice($this->coll, $offset, $length, $replace));
        }
        return new self(array_splice($this->coll, $offset, $length));
    }

    /**
     * array_reduce方法
     *
     * @param callable $function
     * @param mixed $initial
     * @access public
     * @return mixed
     */
    public function reduce($function, $initial = null) {
        return array_reduce($this->coll, $function, $initial);
    }

    /**
     * 自定义排序
     *
     * @param callable $cmp_function
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function usort($cmp_function) {
        usort($this->coll, $cmp_function);
        return $this;
    }

    /**
     * 自定义分组
     * 使用自定义callback方法依次调用每个元素
     * 根据返回的key把所有元素重新分组
     * 返回新的collection
     *
     * @param callable $key_function
     * @param boolean $replace 是否覆盖相同key的元素
     * @access public
     * @return Lysine\Utils\Coll
     */
    public function groupBy($key_function, $replace = false) {
        $group = array();
        foreach ($this->coll as $idx => $el) {
            $key = call_user_func($key_function, $el);
            if ($replace) {
                $group[$key] = $el;
            } else {
                $group[$key][$idx] = $el;
            }
        }

        return new self($group);
    }
}
