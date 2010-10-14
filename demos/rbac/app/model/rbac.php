<?php
namespace Model;

use Lysine\Utils\Singleton;
use Lysine\HttpError;

class Rbac extends Singleton {
    private $default_rule = array(
        'allow' => '*',
    );

    private function getRule($class) {
        $basename = strtolower(app()->getRouter()->getNamespace());
        $class = explode('\\', trim(preg_replace('/^'.$basename.'/', '', strtolower($class)), '\\'));
        $cfg = cfg('app', 'rbac');

        $rule = isset($cfg['_config']) ? $cfg['_config'] : $this->default_rule;

        foreach ($class as $key) {
            if (!isset($cfg[$key])) return $rule;
            if (isset($cfg[$key]['_config'])) $rule = $cfg[$key]['_config'];
            $cfg = $cfg[$key];
        }

        return $rule;
    }

    private function halt($class) {
        throw HttpError::forbidden(array(
            'class' => $class,
            'url' => req()->requestUri(),
        ));
    }

    public function check($class) {
        $rule = $this->getRule($class);
        $user = User::current();

        if (isset($rule['deny'])) {
            if ($rule['deny'] == '*') return $this->halt($class);
            foreach (explode(',', $rule['deny']) as $role) {
                if ($user->hasRole(trim($role))) return $this->halt($class);
            }
        }

        if (isset($rule['allow'])) {
            if ($rule['allow'] == '*') return true;
            foreach (explode(',', $rule['allow']) as $role) {
                if ($user->hasRole(trim($role))) return true;
            }
            return $this->halt($class);
        }
    }
}
