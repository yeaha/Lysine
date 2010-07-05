<?php
namespace Lysine\Utils;

/**
 * Container类
 * 可注入自定义方法
 *
 * Example:
 * $obj = new Container();
 * $obj->hello = function($obj, $name) { return "Hello, {$name}"; };
 * echo $obj->hello('yangyi');
 *
 * @package Utils
 * @author Yang Yi <yangyi.cn.gz@gmail.com>
 */
class Injection {
    protected $method;

    /**
     * 注入方法
     * 注入必须以闭包的方式
     *
     * @param mixed $fn
     * @param Closure $closure
     * @final
     * @access public
     * @return void
     */
    final public function inject($fn, $closure = null) {
        if (is_array($fn)) {
            while (list($k, $v) = each($fn)) $this->inject($k, $v);
        } else {
            if (!is_object($closure) OR (get_class($closure) != 'Closure'))
                throw new \InvalidArgumentException('Container __set() parameter 2 need a closure');
            $this->method[$fn] = $closure;
        }
        return $this;
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
        if (!array_key_exists($fn, $this->method))
            throw new \BadMethodCallException('Call bad injection method '. $fn);

        array_unshift($args, $this);
        return call_user_func_array($this->method[$fn], $args);
    }
}
