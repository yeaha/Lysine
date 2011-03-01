<?php
namespace Lysine\Utils;

/**
 * 集合对象
 * 对象包装起来的数组
 *
 * @package Utils
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Set implements \ArrayAccess, \Countable, \IteratorAggregate {
    /**
     * 集合数据
     *
     * @var array
     * @access protected
     */
    protected $set = array();

    /**
     * 构造函数
     *
     * @param array $elements
     * @access public
     * @return void
     */
    public function __construct(array $elements = array()) {
        $this->set = $elements;
    }

    /**
     * 析构函数
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        $this->set = null;
    }

    /**
     * 还原为数组
     *
     * @access public
     * @return array
     */
    public function toArray() {
        return $this->set;
    }

    /**
     * IteratorAggregate接口
     * 返回迭代子
     *
     * @access public
     * @return ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->set);
    }

    /**
     * Countable接口
     *
     * @access public
     * @return integer
     */
    public function count() {
        return count($this->set);
    }

    /**
     * ArrayAccess接口
     *
     * @param mixed $offset
     * @access public
     * @return boolean
     */
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->set);
    }

    /**
     * ArrayAccess接口
     *
     * @param mixed $offset
     * @access public
     * @return mixed
     */
    public function offsetGet($offset) {
        return array_key_exists($offset, $this->set)
             ? $this->set[$offset]
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
        $this->set[$offset] = $val;
    }

    /**
     * ArrayAccess接口
     *
     * @param mixed $offset
     * @access public
     * @return void
     */
    public function offsetUnset($offset) {
        unset($this->set[$offset]);
    }

    /**
     * 获得所有元素的key
     *
     * @access public
     * @return array
     */
    public function getKeys() {
        return array_keys($this->set);
    }

    /**
     * 获得所有元素的value
     *
     * @access public
     * @return array
     */
    public function getValues() {
        return array_values($this->set);
    }

    /**
     * 获得第一个元素
     *
     * @access public
     * @return mixed
     */
    public function first() {
        return reset($this->set);
    }

    /**
     * 获得最后一个元素
     *
     * @access public
     * @return mixed
     */
    public function last() {
        return end($this->set);
    }

    /**
     * 返回并删除第一个元素
     *
     * @access public
     * @return mixed
     */
    public function shift() {
        return array_shift($this->set);
    }

    /**
     * 把元素插入到数组第一位
     *
     * @param mixed $element
     * @access public
     * @return Lysine\Utils\Set
     */
    public function unshift($element) {
        $args = func_get_args();
        array_splice($this->set, 0, 0, $args);
        return $this;
    }

    /**
     * 返回并删除最后一个元素
     *
     * @access public
     * @return mixed
     */
    public function pop() {
        return array_pop($this->set);
    }

    /**
     * 把元素插入到数组尾部
     *
     * @param mixed $element
     * @access public
     * @return Lysine\Utils\Set
     */
    public function push($element) {
        $args = func_get_args();
        array_splice($this->set, count($this->set), 0, $args);
        return $this;
    }

    /**
     * array_merge
     * 返回新的Set
     *
     * @param mixed $others
     * @access public
     * @return Lysine\Utils\Set
     */
    public function merge($others) {
        $args = func_get_args();
        foreach ($args as $k => $arg)
            if ($arg instanceof Set) $args[$k] = $arg->toArray();

        array_unshift($args, $this->set);
        $result = call_user_func_array('array_merge', $args);
        return new static($result);
    }

    /**
     * 把每个元素作为参数传递给function
     * 返回新的Set
     *
     * @param callback $fn
     * @access public
     * @return Lysine\Utils\Set
     */
    public function map($fn) {
        return new static(array_map($fn, $this->set));
    }

    /**
     * 把每个元素作为参数传递给function
     *
     * @param callback $fn
     * @access public
     * @return void
     */
    public function each($fn) {
        return array_walk($this->set, $fn);
    }

    /**
     * 把数组中的每个元素作为参数传递给function
     * 找出符合条件的值
     * 返回新的Set
     *
     * @param callback $fn
     * @access public
     * @return Lysine\Utils\Set
     */
    public function filter($fn) {
        return new static(array_filter($this->set, $fn));
    }

    /**
     * 把每个元素用function调用
     * 检查是否每个调用都返回真
     *
     * @param callback $fn
     * @access public
     * @return boolean
     */
    public function every($fn) {
        foreach ($this->set as $el)
            if (!call_user_func($fn, $el)) return false;
        return true;
    }

    /**
     * 把每个元素用function调用
     * 检查是否至少有一次调用结果为真
     *
     * @param callback $fn
     * @access public
     * @return boolean
     */
    public function some($fn) {
        foreach ($this->set as $el)
            if (call_user_func($fn, $el)) return true;
        return false;
    }

    /**
     * array_slice方法
     * 返回新的set
     *
     * @param integer $offset
     * @param integer $length
     * @param boolean $preserve_keys
     * @access public
     * @return Lysine\Utils\Set
     */
    public function slice($offset, $length = null, $preserve_keys = false) {
        return new static(array_slice($this->set, $offset, $length, $preserve_keys));
    }

    /**
     * array_splice方法
     * 返回新的set
     *
     * @param integer $offset
     * @param integer $length
     * @param mixed $replace
     * @access public
     * @return Lysine\Utils\Set
     */
    public function splice($offset, $length = 0, $replace = null) {
        $args = func_get_args();
        if (count($args) > 2) {
            $replace = $args[2];
            array_splice($this->set, $offset, $length, $replace);
            return $this;
        }
        return new static(array_splice($this->set, $offset, $length));
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
        return array_reduce($this->set, $function, $initial);
    }

    /**
     * array_chunk方法
     * 返回新的Set
     *
     * @param integer $size
     * @param boolean $preserve_keys
     * @access public
     * @return Lysine\Utils\Set
     */
    public function chunk($size, $preserve_keys = false) {
        return new static(array_chunk($this->set, $size, $preserve_keys));
    }

    /**
     * array shuffle()
     *
     * @access public
     * @return self
     */
    public function shuffle() {
        $new_set = array();

        // 直接shuffle $this->set会丢失key
        // 所以对key做shuffle()
        $keys = $this->getKeys();
        shuffle($keys);

        foreach ($keys as $key)
            $new_set[$key] = $this->set[$key];
        $this->set = $new_set;

        return $this;
    }

    /**
     * 自定义排序
     * 修改自身
     *
     * @param callable $cmp_function
     * @access public
     * @return Lysine\Utils\Set
     */
    public function sortBy($cmp_function) {
        uasort($this->set, $cmp_function);
        return $this;
    }

    /**
     * 自定义分组
     * 使用自定义function依次调用每个元素
     * 根据返回的key把所有元素重新分组
     * 修改自身
     *
     * @param callable $fn
     * @param boolean $replace 是否覆盖相同key的元素
     * @access public
     * @return Lysine\Utils\Set
     */
    public function groupBy($fn, $replace = false) {
        $group = array();
        foreach ($this->map($fn) as $idx => $result) {
            if ($replace) {
                $group[$result] = $this->set[$idx];
            } else {
                $group[$result][$idx] = $this->set[$idx];
            }
        }

        $this->set = $group;
        return $this;
    }
}
