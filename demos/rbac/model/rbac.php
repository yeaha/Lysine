<?php
namespace Model;

use Lysine\HttpError;
use Model\User;

class Rbac {
    static private $instance;

    static public function instance() {
        return self::$instance ?: (self::$instance = new static);
    }

    private function halt() {
        throw User::current()->hasRole('anonymous')     // login?
            ? HttpError::unauthorized()                 // 401
            : HttpError::forbidden();                   // 403
    }

    private function execute($rule) {
        if (isset($rule['deny'])) {
            if ($rule['deny'] == '*') $this->halt();

            if (array_intersect(
                User::current()->getRoles(),
                preg_split('/\s*,\s*/', $rule['deny'])
            )) $this->halt();
        }

        if (isset($rule['allow'])) {
            if ($rule['allow'] == '*') return true;

            if (array_intersect(
                User::current()->getRoles(),
                preg_split('/\s*,\s*/', $rule['allow'])
            )) return true;

            // 如果设置了allow，但是当前登录用户又没有包括这些角色，就不允许访问
            $this->halt();
        }

        // 返回false会继续检查上一级rule
        return false;
    }

    public function check($url, $class) {
        $rules = cfg('app', 'rbac');
        $token = strtolower(trim($class, '\\'));

        try {
            // 从下向上一层一层检查namespace权限设置
            $pos = null;
            do {
                if ($pos !== null)
                    $token = substr($token, 0, $pos);

                if (isset($rules[$token]) && $this->execute($rules[$token]))
                    return true;

                $pos = strrpos($token, '\\');
            } while ($pos !== false);

            $this->execute($rules['__default__']);
        } catch (HttpError $ex) {
            $ex->url = $url;
            $ex->class = $class;

            throw $ex;
        }
    }
}
