<?php

require_once(dirname(__FILE__) . '/config.php');

$site = Zend_Registry::get('site');

$application = $site->getInstance( dirname(__FILE__) . '/..' );

Zend_Registry::set('application', $application);

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];
