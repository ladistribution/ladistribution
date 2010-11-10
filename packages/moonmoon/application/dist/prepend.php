<?php

require_once dirname(__FILE__) . '/config.php';

$site = Zend_Registry::get('site');
$application = $site->getInstance( dirname(__FILE__) . '/..' );
Zend_Registry::set('application', $application);

if (empty($_GET['type'])) {
    $_GET['type'] = 'minimal';
}
