#!/usr/bin/php
<?php
$args = $_SERVER['argv'];

if (!isset($args[1]) OR !is_dir($args[1])) die("Example: php -q ${args[0]} /path \n");

$path = realpath($args[1]) .'/';

$map = array();
foreach (files($path) as $file) {
    $file_name = str_replace($path, '', $file);
    foreach (getClass($file) as $class_name)
        $map[$class_name] = $file_name;
}
ksort($map);
$out = var_export($map, true);

echo <<< EOF
<?php
return {$out};
EOF;

function files($dir) {
    $files = array();
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    if (is_dir($dir)) {
        $handle = opendir($dir);
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' OR $file == '..') continue;
            $file = $dir .DIRECTORY_SEPARATOR. $file;
            if (is_dir($file)) {
                $files = array_merge($files, files($file));
            } else {
                if (preg_match('/\.php$/', $file)) $files[] = $file;
            }
        }
        closedir($handle);
    } elseif (is_file($dir)) {
        return array($dir);
    }
    return $files;
}

function getClass($file) {
    $class = array();
    $source = file_get_contents($file);
    $tokens = token_get_all($source);

    $catch = false;
    while (list(, $token) = each($tokens)) {
        if (!is_array($token)) continue;
        $tname = token_name($token[0]);
        if ($tname == 'T_CLASS' OR $tname == 'T_INTERFACE') $catch = true;
        if ($catch && $tname == 'T_STRING') {
            $class[] = $token[1];
            $catch = false;
        }
    }
    return $class;
}
