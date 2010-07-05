<?php
namespace Lysine {
    /**
     * url路由
     *
     * @package base
     * @author yangyi <yangyi@surveypie.com>
     */
    interface IRouter {
        /**
         * 分发请求
         *
         * @param string $url
         * @param array $params
         * @access public
         * @return mixed
         */
        public function dispatch($url, array $params = array());
    }

    /**
     * 缓存类接口
     *
     * @package base
     * @author yangyi <yangyi@surveypie.com>
     */
    interface ICache {
        /**
         * 保存缓存
         *
         * @param string $key
         * @param mixed $val
         * @param integer $life_time
         * @access public
         * @return boolean
         */
        public function set($key, $val, $life_time = null);

        /**
         * 读取缓存
         * 如果没有获得数据会调用$callback
         *
         * @param string $key
         * @param callable $callback
         * @access public
         * @return mixed
         */
        public function get($key, $callback = null);

        /**
         * 删除缓存
         *
         * @param string $key
         * @access public
         * @return boolean
         */
        public function delete($key);
    }
}
