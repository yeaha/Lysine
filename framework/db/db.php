<?php
class Ly_Db {
    static public function factory($dsn, $user, $pass, array $options = array()) {
        if (!preg_match('/^([a-z]+):.+/i', $dsn, $match))
            throw new Ly_Db_Exception('Invalid dsn');

        $adapter = $match[1];

        $class = 'Ly_Db_Adapter_'. ucfirst($adapter);
        return new $class($dsn, $user, $pass, $options);
    }
}

class Ly_Db_Exception extends Ly_Exception {
}
