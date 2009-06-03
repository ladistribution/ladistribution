<?php

define('LD_SESSION', false);

require_once dirname(__FILE__) . '/config.php';

$site = Zend_Registry::get("site");
$application = $site->getInstance( dirname(__FILE__) . "/.." );
$databases = $site->getDatabases();
$db = $databases[ $application->getDb() ];

define("DC_DBDRIVER", "mysql");
define("DC_DBNAME", $db["name"]);
define("DC_DBUSER", $db["user"]);
define("DC_DBPASSWORD", $db["password"]);
define("DC_DBHOST", $db["host"]);
define("DC_DBPREFIX", $application->getDbPrefix());
define('DC_ADMIN_URL', $application->getUrl() . "admin/");

$_SERVER["CLEARBRICKS_PATH"] = LD_LIB_DIR . "/clearbricks";
