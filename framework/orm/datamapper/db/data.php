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
    protected function formatProp($prop, $val, array $prop_meta) {
        $val = parent::formatProp($prop, $val, $prop_meta);

        if ($prop_meta['allow_null']) {
            if ($prop_meta['default'] === $val)
                return null;
        } else {
            if ($val === null)
                return $prop_meta['default'];
        }

        return $val;
    }

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
        return static::getMapper()->select()->setCols(array_keys(static::getMeta()->fieldOfProp()));
    }
}
