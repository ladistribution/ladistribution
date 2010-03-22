<?php

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/functions.php';

$site = Zend_Registry::get('site');

$application = $site->getInstance( dirname(__FILE__) . '/..' );
Zend_Registry::set('instance', $application);

$user = Ld_Auth::getUser();

$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];

$dbPrefix = $application->getDbPrefix();

define('WEAVE_AUTH_ENGINE', 'ld');
define('WEAVE_STORAGE_ENGINE', 'mysql');

define('WEAVE_MYSQL_AUTH_DB', $db['name']);
define('WEAVE_MYSQL_AUTH_USER', $db['user']);
define('WEAVE_MYSQL_AUTH_PASS', $db['password']);
define('WEAVE_MYSQL_AUTH_HOST', $db['host']);

define('WEAVE_MYSQL_STORE_READ_DB', $db['name']);
define('WEAVE_MYSQL_STORE_READ_USER', $db['user']);
define('WEAVE_MYSQL_STORE_READ_PASS', $db['password']);
define('WEAVE_MYSQL_STORE_READ_HOST', $db['host']);

define('WEAVE_MYSQL_STORE_TABLE_NAME', "{$dbPrefix}wbo");
define('WEAVE_MYSQL_COLLECTION_TABLE_NAME', "{$dbPrefix}collections");

// define('WEAVE_REGISTER_USE_CAPTCHA', false);
// define('RECAPTCHA_PUBLIC_KEY', '6LfWcwUAAAAAABnmLyhmgddYeJGdiRlo2MWSOpAl');
// define('RECAPTCHA_PRIVATE_KEY', '6LfWcwUAAAAAAHpjpBNSaxwLVQXQIG-S0Y6IG38O');