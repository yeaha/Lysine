<?php
class Ly_Db {
    static public $conns = array();

    static public function conn($dsn_name = null) {
        if (is_null($dsn_name)) $dsn_name = '__default__';
        if (isset(self::$conns[$dsn_name])) return self::$conns[$dsn_name];

        $cfg = cfg('db', 'dsn', $dsn_name);
        if (!is_array($cfg))
            throw new Ly_Db_Exception('Database config not found!');

        $dsn = isset($cfg['dsn']) ? $cfg['dsn'] : null;
        $user = isset($cfg['user']) ? $cfg['user'] : null;
        $pass = isset($cfg['pass']) ? $cfg['pass'] : null;
        $options = isset($cfg['options']) ? $cfg['options'] : array();

        if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
            throw new Ly_Db_Exception('Invalid dsn');

        $adapter = $match[1];

        $class = 'Ly_Db_Adapter_'. ucfirst($adapter);
        $dbh = new $class($dsn, $user, $pass, $options);

        self::$conns[$dsn_name] = $dbh;
        return self::$conns[$dsn_name];
    }
}

class Ly_Db_Exception extends Ly_Exception {
}
