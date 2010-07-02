<?php
namespace Lysine;

class Db {
    static public function factory($dsn, $user, $pass, array $options = array()) {
        if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
            throw new InvalidArgumentException('Invalid dsn');

        $adapter = $match[1];

        $class = __NAMESPACE__ .'\Db\Adapter\\'. ucfirst($adapter);
        return new $class($dsn, $user, $pass, $options);
    }
}
