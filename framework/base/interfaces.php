<?php
namespace Lysine {
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
         *
         * @param string $key
         * @access public
         * @return mixed
         */
        public function get($key);

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
