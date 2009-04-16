<?php

if (file_exists(dirname(__FILE__) . '/config.php')) {
	require_once dirname(__FILE__) . '/config.php';
}

require_once 'Ld/Site/Local.php';
$site = new Ld_Site_Local();

require_once 'Ld/Instance/Application/Local.php';
$application = new Ld_Instance_Application_Local( dirname(__FILE__) . '/..' );

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];

define('DB_NAME', $db['name']);
define('DB_USER', $db['user']);
define('DB_PASSWORD', $db['password']);
define('DB_HOST', $db['host']);

$table_prefix = $application->getDbPrefix();

require_once 'Ld/Files.php';
Ld_Files::includes(dirname(__FILE__) . '/prepend/');
