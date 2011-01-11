<?php
namespace Lysine\ORM\DataMapper;

use Lysine\ORM\DataMapper\Data;
use Lysine\ORM\DataMapper\MongoMapper;

/**
 * 存储在mongodb中的领域模型
 *
 * @uses Data
 * @abstract
 * @package ORM
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
abstract class MongoData extends Data {
    /**
     * 获得映射关系封装实例
     *
     * @static
     * @access public
     * @return Lysine\ORM\DataMapper\MongoMapper
     */
    static public function getMapper() {
        return MongoMapper::factory(get_called_class());
    }

    /**
     * 通过查询获得实例
     *
     * @param array $query
     * @static
     * @access public
     * @return Lysine\Utils\Set
     */
    static public function findByQuery(array $query) {
        return static::getMapper()->findByQuery($query);
    }
}
