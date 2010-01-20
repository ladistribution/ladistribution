<?php

require_once dirname(__FILE__) . '/config.php';

$site = Zend_Registry::get('site');

$application = $site->getInstance( dirname(__FILE__) . '/..' );

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];

define('DB_NAME', $db['name']);
define('DB_USER', $db['user']);
define('DB_PASSWORD', $db['password']);
define('DB_HOST', $db['host']);

$table_prefix = $application->getDbPrefix();

$locale = $application->getLocale();

if ($locale == 'auto') {
	if (isset($_COOKIE['ld-lang'])) {
		$locale = $_COOKIE['ld-lang'];
	}
}

if (isset($locale) && $locale != 'auto') {
	define('WPLANG', $locale);	
}

if (!defined('WP_CACHE')
	&& file_exists(ABSPATH . 'wp-content/advanced-cache.php')
	&& filesize(ABSPATH . 'wp-content/advanced-cache.php') > 0) {
	define('WP_CACHE', true);
}

require_once 'Ld/Files.php';
Ld_Files::includes(dirname(__FILE__) . '/prepend/');
