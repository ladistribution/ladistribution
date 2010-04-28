<?php

require_once(dirname(__FILE__) . "/config.php");

spl_autoload_register('__autoload');

$site = Zend_Registry::get("site");

$application = $site->getInstance( dirname(__FILE__) . "/.." );
Zend_Registry::set("application", $application);

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];

$configuration = $application->getConfiguration();

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Statusnet:prepend');
}
