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

if (defined('LD_DEBUG') && constant('LD_DEBUG')) {
	define('WP_DEBUG', true);
	define('SCRIPT_DEBUG', true);
	define('COMPRESS_SCRIPTS', false);
	define('CONCATENATE_SCRIPTS', false);
}

if (!defined('WP_CACHE')
	&& file_exists(ABSPATH . 'wp-content/advanced-cache.php')
	&& filesize(ABSPATH . 'wp-content/advanced-cache.php') > 0) {
	define('WP_CACHE', true);
}

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Wordpress:prepend');
}
