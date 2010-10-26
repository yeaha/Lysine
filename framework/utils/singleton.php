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
    static private $instance = array();

    /**
     * 获得唯一实例
     *
     * @static
     * @final
     * @access public
     * @return mixed
     */
    final static public function instance() {
        $class = get_called_class();
        if (!isset(self::$instance[$class]))
            self::$instance[$class] = new $class;

        return self::$instance[$class];
    }
}
