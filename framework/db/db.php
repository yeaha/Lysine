<?php
namespace Lysine;

class Db {
    const TYPE_INTEGER = 1;
    const TYPE_FLOAT = 2;
    const TYPE_BOOL = 3;
    const TYPE_STRING = 4;
    const TYPE_BINARY = 5;

    static protected $default_path = array('db', 'pool', '__default__');

    static protected $adapter = array();

    static public function setDefaultPath(array $path) {
        self::$default_path = $path;
    }

    static public function getDefaultPath() {
        return self::$default_path = $path;
    }

    static public function connect($path = null) {
        if ($path === null) {
            $path = self::$default_path;
        } else {
            $path = is_array($path) ? $path : func_get_args();
        }
        $cfg = cfg($path);

        if (!is_array($cfg) OR !isset($cfg['dsn']))
            throw new \InvalidArgumentException('database config['. implode(', ', $path) .'] not found!');

        $dsn = $cfg['dsn'];
        if (isset(self::$adapter[$dsn])) return self::$adapter[$dsn];

        $user = isset($cfg['user']) ? $cfg['user'] : null;
        $pass = isset($cfg['pass']) ? $cfg['pass'] : null;
        $options = (isset($cfg['options']) AND is_array($cfg['options']))
                 ? $cfg['options']
                 : array();
        $adapter = self::factory($dsn, $user, $pass, $options);
        self::$adapter[$dsn] = $adapter;
        return $adapter;
    }

    static public function factory($dsn, $user, $pass, array $options = array()) {
        if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
            throw new \InvalidArgumentException('Invalid dsn');

        $adapter = $match[1];

        $class = __NAMESPACE__ .'\Db\Adapter\\'. ucfirst($adapter);
        return new $class($dsn, $user, $pass, $options);
    }
}
