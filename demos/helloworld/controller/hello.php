<?php
class Controller_Hello {
    public function get($name) {
        return app()->forward('/hi/'. $name);
        //return sprintf('Hello %s', $name);
    }

    public function post() {
    }

    public function ajax() {
    }

    public function ajax_get() {
    }

    public function ajax_post() {
    }
}
