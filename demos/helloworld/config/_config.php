<?php
return array(
    'app' => array(
        'view' => array(
            'view_dir' => ROOT_DIR .'/view',
        ),
    ),
    'storages' => array(
        'sys_log' => array(
            'class' => 'Lysine\Storage\File',
            'filename' => ROOT_DIR .'/logs/sys_%Y-%m-%d.log',
        ),
    ),
);
