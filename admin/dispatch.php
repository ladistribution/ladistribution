<?php

$dir = dirname(__FILE__);

require_once($dir . '/dist/config.php');

$site = Zend_Registry::get('site');

// Load admin if needed
$path = str_replace($site->getPath(), '', $_SERVER["REQUEST_URI"]);
$modules = Ld_Files::getDirectories($site->getDirectory('shared') . '/modules');
$modules[] = 'auth';
foreach ($modules as $module) {
    if (strpos($path, $module) === 1) {
        $root_application = 'admin';
    }
}

$default_root_application = 'admin';

// Get Root Application
if (empty($root_application)) {
    $root_application = $site->getConfig('root_application');
    if (empty($root_application)) {
        $root_application = $default_root_application;
    }
}

// Get Instance
$instance = $site->getInstance($root_application);
if (empty($instance)) {
    $root_application = $default_root_application;
    $instance = $site->getInstance($root_application);
}

switch ($instance->getPackageId()) {
    case 'dokuwiki':
        $script = 'doku.php';
        break;
    default:
        $script = 'index.php';
}

chdir($root_application);
require_once($script);
