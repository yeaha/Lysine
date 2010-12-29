<?php
return array(
    '__default__' => array(
        'class' => '\Lysine\Storage\DB\Adapter\Pgsql',
        'dsn' => 'pgsql:host=127.0.0.1 dbname=lysine.rbac',
        'user' => 'dev',
        'pass' => 'abc',
    ),
    'lysine_log' => array(
        'class' => '\Lysine\Storage\File',
        'filename' => ROOT_DIR .'/logs/lysine_%Y%m%d.log',
    ),
);
