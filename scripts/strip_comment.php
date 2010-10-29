#!/usr/bin/php
<?php
// Example: php -q strip_comment.php [/path]
$args = $_SERVER['argv'];
$path = isset($args[1]) ? $args[1] : './';

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($path)));
foreach ($it as $fi) {
    if (!$fi->isFile()) continue;
    $filename = $fi->getRealPath();
    echo $filename . PHP_EOL;
    $code = file_get_contents($filename);
    file_put_contents( $filename, strip_comment($code) );
}

function strip_comment($code) {
    $result  = '';
    $commentTokens = array(T_COMMENT, T_DOC_COMMENT);

    foreach (token_get_all($code) as $token) {    
        if (is_array($token)) {
            if (in_array($token[0], $commentTokens))
                continue;

            $token = $token[1];
        }

        $result .= $token;
    }

    $result = preg_replace('/\n\s*\n/', "\n", $result);

    return $result;
}
