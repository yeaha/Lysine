<?php
return array(
    'app' => array(
        'view' => array(
            'view_dir' => ROOT_DIR .'/app/view',
        ),
        'rbac' => require ROOT_DIR .'/config/_rbac.php',
    ),
    'storage' => require ROOT_DIR .'/config/_storage.php',
);
