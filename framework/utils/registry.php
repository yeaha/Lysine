<?php
/**
 * 全局注册表
 * 可以在程序运行期以key-value的方式保存任意数据
 *
 * @package Utils
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
namespace Lysine\Utils;

class Registry {
    static private $items = array();

    /**
     * 保存数据
     *
     * @param string $key
     * @param mixed $val
     * @static
     * @access public
     * @return void
     */
    static public function set($key, $val) {
        self::$items[$key] = $val;
    }

    /**
     * 获取数据
     * 数据不存在返回false
     *
     * @param string $key
     * @static
     * @access public
     * @return mixed
     */
    static public function get($key) {
        return array_key_exists($key, self::$items) ? self::$items[$key] : false;
    }

    /**
     * 是否有指定数据
     *
     * @param string $key
     * @static
     * @access public
     * @return boolean
     */
    static public function has($key) {
        return array_key_exists($key, self::$items);
    }

    /**
     * 删除保存的数据
     *
     * @param string $key
     * @static
     * @access public
     * @return void
     */
    static public function remove($key) {
        unset(self::$instance[$key]);
    }
}
