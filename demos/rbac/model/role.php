<?php
namespace Model;

use Lysine\DataMapper\DBData;

/**
 * 角色
 *
 * @uses DBData
 * @package Model
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Role extends DBData {
    static protected $collection = 'public.roles';
    static protected $props_meta = array(
        // 角色编号
        'id' => array('type' => 'int', 'primary_key' => true),
        // 角色名
        'name' => array('type' => 'string'),
    );

    static public function findByName($name) {
        return static::select()->where('name = ?', $name)->get(1);
    }
}
