<?php

define('LD_ROOT_CONTEXT', true);

require_once(dirname(__FILE__) . '/dist/site.php');

$site = Zend_Registry::get('site');

$root_application = $site->getConfig('root_application');
if (empty($root_application)) {
    $root_application =  'admin';
}

$instance = $site->getInstance($root_application);

switch ($instance->getPackageId()) {
    case 'dokuwiki':
        $script = 'doku.php';
        break;
    default:
        $script = 'index.php';
}

chdir($root_application);
require_once($script);