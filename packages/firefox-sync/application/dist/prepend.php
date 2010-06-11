<?php

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/functions.php';

$site = Zend_Registry::get('site');

$application = $site->getInstance( dirname(__FILE__) . '/..' );

$user = Ld_Auth::getUser();

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];
if (strpos($db['host'], ':')) {
    list($db['host'], $db['port']) = explode(':', $db['host']);
}
if (empty($db['port'])) {
    $db['port'] = '3306';
}

$dbPrefix = $application->getDbPrefix();

define('WEAVE_AUTH_ENGINE', 'ld');
define('WEAVE_STORAGE_ENGINE', 'mysql');

define('WEAVE_MYSQL_STORE_READ_DB', $db['name']);
define('WEAVE_MYSQL_STORE_READ_USER', $db['user']);
define('WEAVE_MYSQL_STORE_READ_PASS', $db['password']);
define('WEAVE_MYSQL_STORE_READ_HOST', $db['host']);
define('WEAVE_MYSQL_STORE_READ_PORT', $db['port']);

define('WEAVE_MYSQL_STORE_TABLE_NAME', "{$dbPrefix}wbo");
define('WEAVE_MYSQL_COLLECTION_TABLE_NAME', "{$dbPrefix}collections");

// avoid E_NOTICE errors. since weave is a web service,
// they can be difficult to trace and break JSON output
error_reporting( E_ALL ^ E_NOTICE );

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Weave:prepend');
}

if (strpos($application->getCurrentPath(), '/1.0/') !== false && strpos($_SERVER["SCRIPT_FILENAME"], '/sync/1.0/') === false) {
	$dir = $application->getAbsolutePath() . '/sync/1.0/';
	chdir($dir);
	require 'index.php';
	exit;
}
