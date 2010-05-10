<?php

require_once(dirname(__FILE__) . "/config.php");

$site = Zend_Registry::get("site");

$application = $site->getInstance( dirname(__FILE__) . "/.." );
Zend_Registry::set("application", $application);

if (empty($_REQUEST['p'])) {
	$_REQUEST['p'] = substr($application->getCurrentPath(), 1);
}

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Statusnet:prepend');
}
