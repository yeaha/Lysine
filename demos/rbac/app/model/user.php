<?php
namespace Model;

use Lysine\ORM\DataMapper\DBData;
use Model\Role;

/**
 * 网站用户
 *
 * @uses DBData
 * @package Model
 * @author yangyi <yangyi.cn.gz@gmail.com>
 * @collection public.users
 */
class User extends DBData {
    static private $instance;

    /**
     * 用户编号
     *
     * @var mixed
     * @access protected
     * @primary_key
     */
    protected $id;

    /**
     * Email
     *
     * @var string
     * @access protected
     */
    protected $email;

    /**
     * 密码 md5
     *
     * @var string
     * @access protected
     */
    protected $passwd;

    /**
     * 注册时间
     *
     * @var datetime
     * @access protected
     */
    protected $create_time;

    /**
     * 最后更新时间
     *
     * @var datetime
     * @access protected
     */
    protected $update_time;

    /**
     * 用户角色
     *
     * @var array
     * @access protected
     * @internal
     * @getter getRoles
     * @readonly
     */
    protected $roles;

    public function __construct() {
        if ($login = self::getCookie()) {
            $this->__fill($login['user']);
            $this->roles = $login['roles'];
        }
    }

    public function addRole($role, $expire_time = null) {
        if (!$user_id = $this->id()) throw new \Exception('Cannot add role to unsaved user');

        if ($this->hasRole($role)) return true;

        $role = Role::findByName($role);
        if (!$role) throw new \Exception('Invalid role name');

        $user_role = new User_Role();
        $user_role->set(array(
            'user_id' => $user_id,
            'role_id' => $role->id(),
            'expire_time' => $expire_time
        ));
        if ($saved = $user_role->save()) $this->roles = null;
        return $saved;
    }

    public function removeRole($role) {
        $remove = User_Role::select()
                    ->where('user_id = ?', $this->id())
                    ->whereIn('role_id',
                        Role::select()->setCols('id')->where('name = ?', $role)
                    )->delete();
        if ($remove) $this->roles = null;
        return $remove;
    }

    // return array('role1', 'role2', 'role3', ...);
    protected function getRoles() {
        if (!$id = $this->id()) return array('anonymous');
        if ($this->roles !== null) return $this->roles;

        $user_role_select = User_Role::select()->setCols('role_id')
                                               ->where('user_id = ?', $id)
                                               ->where('expire_time is null or expire_time > now()');
        return $this->roles = Role::select()->setCols('name')
                                            ->whereIn('id', $user_role_select)
                                            ->getCols();
    }

    public function hasRole($role_name) {
        return in_array($role_name, $this->getRoles());
    }

    public function logout() {
        // remove cookie
        self::$instance = null;
        $this->fireEvent('logout');
        self::removeCookie();
        return true;
    }

    // 当前用户
    static public function current() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    // return 登录后的User实例
    static public function login($email, $passwd) {
        $user = static::select()->where('email = ? and passwd = ?', $email, md5($passwd))->get(1);
        if (!$user) return false;
        self::setCookie($user);
        return self::$instance = $user;
    }

    // 清除登录数据cookie
    static private function removeCookie() {
        setcookie('login', null);
    }

    // 保存登录数据到客户端cookie
    static private function setCookie($user, $expire = 0) {
        // TODO: 加密data
        $data = json_encode(array(
            'user' => $user->toArray(),
            'roles' => $user->roles,
        ));
        setcookie('login', $data, $expire, '/', null, false, true);
    }

    // 从客户端cookie获得登录数据
    static private function getCookie() {
        // TODO: 解密data
        return ($data = cookie('login')) ? json_decode($data, true) : false;
    }
}

/**
 * 用户角色关系
 *
 * @uses DBData
 * @package Model
 * @author yangyi <yangyi.cn.gz@gmail.com>
 * @collection public.user_role
 */
class User_Role extends DBData {
    /**
     * 主健编号
     *
     * @var integer
     * @access protected
     * @primary_key
     */
    protected $id;

    /**
     * 用户编号
     *
     * @var integer
     * @access protected
     */
    protected $user_id;

    /**
     * 角色编号
     *
     * @var integer
     * @access protected
     */
    protected $role_id;

    /**
     * 过期时间
     *
     * @var datetime
     * @access protected
     */
    protected $expire_time;

    /**
     * 角色名
     *
     * @var string
     * @access protected
     * @internal
     * @getter getRoleName
     */
    protected $role_name;

    protected function getRoleName() {
        return Role::find($this->role_id)->name;
    }
}
