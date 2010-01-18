<?php

$dir = dirname(__FILE__);

require_once($dir . '/dist/config.php');

$site = Zend_Registry::get('site');

function str_replace_once($needle, $replace, $haystack)
{
    $pos = strpos($haystack, $needle);
    if ($pos === false) {
        return $haystack;
    }
    return substr_replace($haystack, $replace, $pos, strlen($needle));
}

// Load admin if needed
if ($site->getConfig('root_admin') == 1) {
    $path = $_SERVER["REQUEST_URI"];
    $path = str_replace_once($site->getPath() . '/', '', $_SERVER["REQUEST_URI"]);
    $parts = explode('/', $path);
    if (!empty($parts)) {
        $modules = Ld_Files::getDirectories($site->getDirectory('shared') . '/modules');
        $modules[] = 'auth';
        foreach ($modules as $module) {
            if ($parts[0] == $module) {
                $root_application = 'admin';
                break;
            }
        }
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
