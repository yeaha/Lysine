<?php
class Controller_Hello extends Ly_Controller {
    public function get($name) {
        return sprintf('Hello %s', $name);
    }
}
