<?php
return array(
    // application基本配置信息
    'app' => array(
        // 指定request数据使用的类
        // 可以自己写一个类来替代默认的类
        // 唯一的要求是必须继承自Ly_Request
        'request_class' => 'Ly_Request',
    ),

    // 模板视图配置信息
    'view' => array(
        // 存放视图文件的目录
        'view_dir' => APP_PATH .'/view',

        // 视图文件扩展名
        'file_ext' => 'php',
    ),

    /* 数据库连接配置
    'db' => array(
        'dsn' => array(
            '__default__' => array(         // via tcp
                'dsn' => 'psql:host=127.0.0.1 port=5432 dbname=template1',
                'user' => 'dev',
                'pass' => 'abc',
                'options' => array(),
            ),
            '__default__' => array(         // via unix socket
                'dsn' => 'mysql:unix_socket=/var/run/mysq.socket dbname=test',
                'user' => 'dev',
                'pass' => 'abc',
                'options' => array(
                    PDO::ATTR_EMULATE_PREPARES => true
                ),
            ),
        ),
    ),
    */
);
