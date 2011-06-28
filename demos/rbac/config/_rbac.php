<?php
return array(
    '__default__' => array('allow' => '*'),
    'controller\admin' => array('allow' => 'admin'),
    'controller\user' => array('deny' => 'anonymous'),
);
