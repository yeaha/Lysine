<?php
namespace Lysine;

class Db {
    const TYPE_INTEGER = 1;
    const TYPE_FLOAT = 2;
    const TYPE_BOOL = 3;
    const TYPE_STRING = 4;
    const TYPE_BINARY = 5;

    static public function parseConfig(array $cfg) {
        if (!isset($cfg['dsn']))
            throw new \InvalidArgumentException('Invalid database config');

        $dsn = $cfg['dsn'];

        $user = isset($cfg['user']) ? $cfg['user'] : null;
        $pass = isset($cfg['pass']) ? $cfg['pass'] : null;
        $options = (isset($cfg['options']) AND is_array($cfg['options']))
                 ? $cfg['options']
                 : array();

        return array($dsn, $user, $pass, $options);
    }

    static public function factory($dsn, $user, $pass, array $options = array()) {
        if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
            throw new \InvalidArgumentException('Invalid dsn');

        $adapter = $match[1];

        $class = __NAMESPACE__ .'\Db\Adapter\\'. ucfirst($adapter);
        return new $class($dsn, $user, $pass, $options);
    }
}
