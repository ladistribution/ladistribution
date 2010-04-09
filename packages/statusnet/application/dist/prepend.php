<?php

require_once(dirname(__FILE__) . "/config.php");

spl_autoload_register('__autoload');

$site = Zend_Registry::get("site");

$application = $site->getInstance( dirname(__FILE__) . "/.." );

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];

$configuration = $application->getConfiguration();

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Statusnet:prepend');
}

require_once 'Ld/Files.php';
Ld_Files::includes(dirname(__FILE__) . '/prepend/');
