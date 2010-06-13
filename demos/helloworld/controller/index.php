<?php
class Controller_Index {
    public function get() {
        return 'Hello world!';
    }

    public function post() {
        return 'Hello world!';
    }

    public function ajax_get() {
        return new MyResponse();
    }

    public function ajax_post() {
        return new MyResponse();
    }
}

class MyResponse {
    public function __toString() {
        return 'Hello world!';
    }
}
