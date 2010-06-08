<?php
class Ly_Coll extends ArrayObject {
    /**
     * 把每个元素作为参数传递给callback
     * 把所有的返回值以Ly_Coll方式返回
     *
     * @param mixed $callback
     * @access public
     * @return Ly_Coll
     */
    public function map($callback) {
    }

    /**
     * 把数组中的每个元素作为参数传递给callback
     * 保留callback返回true的值
     *
     * @param mixed $callback
     * @access public
     * @return Ly_Coll
     */
    public function filter($callback) {
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
    }
}
