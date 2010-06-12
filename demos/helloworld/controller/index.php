<?php
class Controller_Index {
    protected $resp = 'hello world';

    public function get() {
        return $this->resp;
    }

    public function post() {
        return $this->resp;
    }

    public function ajax_get() {
        return $this->resp;
    }

    public function ajax_post() {
        return $this->resp;
    }
}
