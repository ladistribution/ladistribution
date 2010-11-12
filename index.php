<?php
define('LD_ROOT_CONTEXT', true);
$dir = dirname(__FILE__);
if (file_exists($dir . '/dist/site.php')) {
    require_once($dir . '/dist/site.php');
    list($directory, $script) = Ld_Dispatch::dispatch();
    chdir($directory);
    require_once($script);
} else {
    echo 'La Distribution not installed.';
}