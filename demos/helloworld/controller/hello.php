<?php
class Controller_Hello {
    public function get($name) {
        return render_view('hello', array('name' => $name));
        // return app()->forward('/hi/'. $name);
    }
}
