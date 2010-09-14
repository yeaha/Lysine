<?php
if (!extension_loaded('phar')) die("Your php phar is disabled.\n");

if (ini_get('phar.readonly')) die("Your phar.readonly is ON");

$root_dir = realpath(__DIR__ .'/../');

$phar = new Phar("{$root_dir}/lysine.phar", 0, 'lysine.phar');
$phar->buildFromDirectory("{$root_dir}/framework");
$phar->setStub("<?php
try {
    Phar::loadPhar('/path/to/lysine.phar');
    require 'phar://lysine.phar/core.php';
} catch (PharException \$e) {
    echo \$e->getMessage();
    die('Cannot initialize Phar');
}
__HALT_COMPILER();
?>");
$phar->stopBuffering();

echo "Done! File save at: {$root_dir}/lysine.phar\n";
