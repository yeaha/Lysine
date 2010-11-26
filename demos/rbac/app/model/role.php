<?php
namespace Model;

use Lysine\ORM\DataMapper\DBData;

/**
 * 角色
 *
 * @uses DBData
 * @package Model
 * @author yangyi <yangyi.cn.gz@gmail.com>
 * @collection public.roles
 */
class Role extends DBData {
    /**
     * 角色编号
     *
     * @var integer
     * @access protected
     * @primary_key true
     */
    protected $id;

    /**
     * 角色名
     *
     * @var string
     * @access protected
     */
    protected $name;

    static public function findByName($name) {
        return static::select()->where('name = ?', $name)->get(1);
    }
}
