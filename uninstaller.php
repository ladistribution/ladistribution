<?php

// define('LD_DEBUG', true);

if (empty($_GET['confirm'])) {
    exit;
}

$root = dirname(__FILE__);

require_once $root . '/dist/site.php';

$site = Zend_Registry::get('site');

foreach ($site->getInstances() as $id => $infos) {
    if ($infos['type'] == 'application') {
        $instance = $site->getInstance($id);
        if ($instance) {
            try {
                $site->deleteInstance($instance);
            } catch (Exception $e) {
                echo "- Can't delete application on path '$path'. " . $e->getMessage() . '<br>';
            }
        }
    }
}

Ld_Files::purgeTmpDir(0);

$directories = array('js', 'css', 'shared', 'lib', 'dist', 'tmp');
foreach ($directories as $id) {
    $path = $site->getDirectory($id);
    echo $path . "\n<br>";
    Ld_Files::unlink($path);
}


Ld_Files::unlink('.htaccess');
Ld_Files::unlink('index.php');

echo " - Uninstall OK";
