#!/usr/bin/php
<?php
$args = $_SERVER['argv'];

if (!isset($args[1]) OR !is_dir($args[1])) die("Example: php -q ${args[0]} /path \n");

$path = realpath($args[1]) .'/';

$map = array();
foreach (files($path) as $file) {
    $code = file_get_contents($file);

    $re = '/((abstract )?class|interface) ([^\s]+)/i';
    if (!preg_match($re, $code, $match)) continue;
    $class_name = $match[3];
    $file_name = str_replace($path, '', $file);
    $map[] = sprintf("    '%s' => '%s'", $class_name, $file_name);
}
$map = implode(",\n", $map);

echo <<< EOF
<?php
return array(
{$map}
);
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
                $files[] = $file;
            }
        }
        closedir($handle);
    } elseif (is_file($dir)) {
        return array($dir);
    }
    return $files;
}
