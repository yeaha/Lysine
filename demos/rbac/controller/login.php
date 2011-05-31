<?php
namespace Controller;

use Model\User;

class Login {
    public function get() {
        return render_view('login');
    }

    public function post() {
        if ($user = User::login(post('email'), post('passwd'))) {
            $next = get('ref') ?: '/user';
            return app()->redirect($next);
        }

        return app()->redirect(req()->referer() ?: '/login');
    }
}
