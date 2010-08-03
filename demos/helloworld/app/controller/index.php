<?php
namespace Controller;

class index {
    public function get() {
        return render_view('index', array('output' => 'Hello world!'));
    }

    public function post() {
        return render_view('index', array('output' => 'Hello world!'));
    }

    public function ajax() {
        return new MyResponse();
    }
}

class MyResponse {
    public function __toString() {
        return 'Hello world!';
    }
}
