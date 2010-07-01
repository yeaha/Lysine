<?php
namespace Lysine {
    interface IRouter {
        public function dispatch($url, array $params = array());
    }
}
