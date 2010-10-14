<?php
namespace Controller;

class User {
    public function get() {
        return render_view('user', array('user' => \Model\User::current()));
    }
}
