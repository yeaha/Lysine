<?php
return array(
    'Controller' => array(
        '_config' => array(
            'allow' => '*',
        ),
        'admin' => array(
            '_config' => array(
                'allow' => 'admin',
            ),
        ),
        'user' => array(
            '_config' => array(
                'deny' => 'anonymous',
            ),
        ),
    ),
);
