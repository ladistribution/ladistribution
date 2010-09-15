<?php

$dir = dirname(__FILE__);

if (file_exists($dir . '/dist/config.php')) {
    require_once($dir . '/dist/config.php');
    list($directory, $script) = Ld_Dispatch::dispatch();
    chdir($directory);
    require_once($script);
} else {
    echo 'La Distribution not installed.';
    exit;
}
