<?php

require_once dirname(__FILE__) . '/config.php';

$site = Zend_Registry::get('site');

$application = $site->getInstance( dirname(__FILE__) . '/..' );

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];

define('BBDB_NAME', $db['name']);
define('BBDB_USER', $db['user']);
define('BBDB_PASSWORD', $db['password']);
define('BBDB_HOST', $db['host']);

$bb_table_prefix = $application->getDbPrefix();

$locale = $application->getLocale();

if ($locale == 'auto') {
	if (isset($_COOKIE['ld-lang'])) {
		$locale = $_COOKIE['ld-lang'];
	}
}

if (isset($locale) && $locale != 'auto') {
	define('BB_LANG', $locale);	
}

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Bbpress:prepend');
}

require_once 'Ld/Files.php';
Ld_Files::includes(dirname(__FILE__) . '/prepend/');
