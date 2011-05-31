<?php
namespace Model;

use Lysine\Utils\Singleton;
use Lysine\HttpError;
use Model\User;

class Rbac extends Singleton {
    private $default_rule = array(
        'allow' => '*',
    );

    private function getRule($class) {
        $rules = array();
        foreach (cfg('app', 'rbac') as $namespace => $config) {
            if (!in_namespace($class, $namespace)) continue;
            $rules = $config;
            $class = preg_replace('/^\\\?'. preg_quote($namespace) .'/i', '', $class);
            break;
        }

        $rule = isset($cfg['_config']) ? $cfg['_config'] : $this->default_rule;
        $class = preg_split('/\\\/', $class, null, PREG_SPLIT_NO_EMPTY);

        foreach ($class as $key) {
            if (!isset($rules[$key])) return $rule;
            if (isset($rules[$key]['_config'])) $rule = $rules[$key]['_config'];
            $rules = $rules[$key];
        }

        return $rule;
    }

    private function halt($class) {
        if (User::current()->hasRole('anonymous')) {
            throw HttpError::unauthorized(array(
                'class' => $class,
                'url' => req()->requestUri(),
            ));
        } else {
            throw HttpError::forbidden(array(
                'class' => $class,
                'url' => req()->requestUri(),
            ));
        }
    }

    public function check($url, $class) {
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
