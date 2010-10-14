<?php
namespace Controller;

use Model\User;

class Logout {
    public function get() {
        User::current()->logout();
        return app()->redirect('/');
    }
}
