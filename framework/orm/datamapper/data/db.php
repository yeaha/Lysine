<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM\DataMapper\Data;
use Lysine\ORM\DataMapper\DBMapper;

/**
 * 使用数据库存储方式的领域模型
 *
 * @uses Data
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class DBData extends Data {
    /**
     * 获得数据映射关系封装
     *
     * @static
     * @access public
     * @return void
     */
    static public function getMapper() {
        return DBMapper::factory(get_called_class());
    }

    /**
     * 发起数据库查询
     *
     * @static
     * @access public
     * @return Lysine\Storage\DB\Select
     */
    static public function select() {
        return static::getMapper()->select();
    }
}
