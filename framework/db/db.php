<?php
class Ly_Db {
    static public $conns = array();

    static public function parseDsn(array $cfg) {
        if (!isset($cfg['adapter']))
            throw new Ly_Db_Exception('Please specify db adapter');

        $adapter = $cfg['adapter'];
        $user = isset($cfg['user']) ? $cfg['user'] : null;
        $pass = isset($cfg['pass']) ? $cfg['pass'] : null;
        $options = isset($cfg['options']) ? $cfg['options'] : array();

        unset($cfg['adapter'], $cfg['user'], $cfg['pass'], $cfg['options']);

        $dsn = array();
        foreach ($cfg as $k => $v) {
            $dsn[] = "{$k}={$v}";
        }

        $dsn = sprintf('%s:%s', $adapter, implode(' ', $dsn));

        return array($adapter, $dsn, $user, $pass, $options);
    }

    static public function conn($dsn_name = null) {
        if (is_null($dsn_name)) $dsn_name = '__default__';
        if (isset(self::$conns[$dsn_name])) return self::$conns[$dsn_name];

        $cfg = cfg('db', $dsn_name);
        if (!is_array($cfg))
            throw new Ly_Db_Exception('Invalid dsn');

        list($adapter, $dsn, $user, $pass, $options) = self::parseDsn($cfg);

        $class = 'Ly_Db_Adapter_'. ucfirst($adapter);
        $dbh = new $class($dsn, $user, $pass, $options);

        self::$conns[$dsn_name] = $dbh;
        return self::$conns[$dsn_name];
    }
}

class Ly_Db_Exception extends Ly_Exception {
}
