<?php
namespace Controller;

class index {
    public function get() {
        return render_view('index', array('output' => 'Hello world!'));
    }

    public function post() {
        return render_view('index', array('output' => 'Hello world!'));
    }
}
