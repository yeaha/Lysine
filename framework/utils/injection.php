<?php
namespace Lysine\Utils;

/**
 * Injection类
 * 可注入自定义方法
 *
 * Example:
 * $obj = new Injection();
 * $obj->hello = function($obj, $name) { return "Hello, {$name}"; };
 * echo $obj->hello('yangyi');
 *
 * @package Utils
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
class Injection {
    protected $method = array();

    /**
     * 注入方法
     *
     * @param mixed $fn
     * @param callable $callable
     * @final
     * @access public
     * @return self
     */
    final public function inject($fn, $callable = null) {
        if (is_array($fn)) {
            foreach ($fn as $k => $v) $this->inject($k, $v);
        } else {
            if (!is_callable($callable))
                throw new \InvalidArgumentException('Injection::inject() parameter 2 is not callable');
            $this->method[$fn] = $callable;
        }
        return $this;
    }

    /**
     * 调用注入的方法
     *
     * @param string $fn
     * @param array $args
     * @access public
     * @return mixed
     */
    final public function call($fn, $args) {
        if (!array_key_exists($fn, $this->method))
            throw new \BadMethodCallException('Call bad injection method '. $fn);

        array_unshift($args, $this);
        return call_user_func_array($this->method[$fn], $args);
    }

    /**
     * 注入方法
     *
     * @param string $fn
     * @param Closure $closure
     * @access public
     * @return void
     */
    public function __set($fn, $closure) {
        $this->inject($fn, $closure);
    }

    /**
     * 调用注入的方法
     *
     * @param string $fn
     * @param array $args
     * @access public
     * @return mixed
     */
    public function __call($fn, $args) {
        return $this->call($fn, $args);
    }
}
