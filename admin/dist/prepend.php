<?php

function config()
{
    $dir = dirname(__FILE__);

    $configs = array(
        $dir . '/config.php', $dir . '/../../dist/config.php'
    );

    foreach ($configs as $config) {
        if (file_exists($config)) {
            require_once $config;
            return true;
        }
    }
    
    throw new Exception('Environment not configured.');
}

config();

require_once 'Ld/Files.php';
Ld_Files::includes(dirname(__FILE__) . '/prepend/');
