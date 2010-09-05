<?php
/**
 * 单例模式
 * 要实现单例模式的类可以选择继承此类
 *
 * @abstract
 * @package Utils
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
namespace Lysine\Utils;

abstract class Singleton {
    /**
     * 唯一实例
     */
    static protected $instance = array();

    /**
     * 获得唯一实例
     *
     * @static
     * @access public
     * @return mixed
     */
    static public function instance() {
        $class = get_called_class();
        if (!isset(self::$instance[$class]))
            self::$instance[$class] = new $class;

        return static::$instance[$class];
    }

    /**
     * 构造函数
     * 无法通过new直接调用
     *
     * @access private
     * @return void
     */
    private function __construct() {
    }
}
