<?php
namespace Lysine;

class Db {
    const TYPE_INTEGER = 1;
    const TYPE_FLOAT = 2;
    const TYPE_BOOL = 3;
    const TYPE_STRING = 4;
    const TYPE_BINARY = 5;

    static protected $pool_path = array('db', 'pool');
    static protected $default_name = '__default__';

    static public function setPoolPath(array $path) {
        self::$pool_path = $path;
    }

    static public function setDefaultName($name) {
        self::$default_name = $name;
    }

    static public function connect($name = null) {
        if ($name === null) $name = self::$default_name;
        $path = self::$pool_path;
        array_push($path, $name);
        $cfg = cfg($path);

        if (!is_array($cfg) OR !isset($cfg['dsn']))
            throw new \InvalidArgumentException('database config['. implode(', ', $path) .'] not found!');

        $dsn = $cfg['dsn'];
        $user = isset($cfg['user']) ? $cfg['user'] : null;
        $pass = isset($cfg['pass']) ? $cfg['pass'] : null;
        $options = (isset($cfg['options']) AND is_array($cfg['options']))
                 ? $cfg['options']
                 : array();
        return self::factory($dsn, $user, $pass, $options);
    }

    static public function factory($dsn, $user, $pass, array $options = array()) {
        if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
            throw new \InvalidArgumentException('Invalid dsn');

        $adapter = $match[1];

        $class = __NAMESPACE__ .'\Db\Adapter\\'. ucfirst($adapter);
        return new $class($dsn, $user, $pass, $options);
    }
}
