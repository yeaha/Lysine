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
$out = array();

foreach ($map as $class => $file) {
    $out[] = sprintf("    '%s' => '%s',", $class, $file);
}
$out = implode("\n", $out);

echo <<< EOF
<?php
return array(
{$out}
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

    $namespace = array();
    $catch_namespace = $catch_class = false;
    while (list(, $token) = each($tokens)) {
        if (!is_array($token)) continue;
        $tname = token_name($token[0]);

        if ($tname == 'T_NAMESPACE') {
            $catch_namespace = true;
            $namespace = array();
            continue;
        }

        if ($catch_namespace AND $tname == 'T_WHITESPACE') continue;

        if ($catch_namespace AND ($tname == 'T_STRING' OR $tname == 'T_NS_SEPARATOR')) {
            $namespace[] = $token[1];
            continue;
        }

        if ($catch_namespace)
            $catch_namespace = !($tname != 'T_STRING' AND $tname != 'T_NS_SEPARATOR');

        if ($tname == 'T_CLASS' OR $tname == 'T_INTERFACE') $catch_class = true;
        if ($catch_class && $tname == 'T_STRING') {
            $class_name = implode('', $namespace) ."\\". $token[1];
            $class[] = $class_name;
            $catch_class = false;
        }
    }
    return $class;
}
