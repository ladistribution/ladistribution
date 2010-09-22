<?php

defined('AJXP_EXEC') or die( 'Access not allowed');

require_once dirname(__FILE__) . "/../../dist/prepend.php";

$site = Zend_Registry::get('site');
$application = Zend_Registry::get('application');

define("ENABLE_USERS", 1);
define("ADMIN_PASSWORD", "admin");
define("ALLOW_GUEST_BROWSING", 0);
define("PUBLIC_DOWNLOAD_FOLDER", $application->getAbsolutePath() . '/public');
define("PUBLIC_DOWNLOAD_URL", "");
define("HTTPS_POLICY_FILE", "");
define("AJXP_TMP_DIR", $site->getDirectory('tmp'));

define("GZIP_DOWNLOAD", false);
define("GZIP_LIMIT", 1*1048576); // Do not Gzip files above 1M
define("DISABLE_ZIP_CREATION", false);

define("GOOGLE_ANALYTICS_ID", "XXXXX");
define("GOOGLE_ANALYTICS_DOMAIN", "");
define("GOOGLE_ANALYTICS_EVENT", false);

$PLUGINS = array(
	"CONF_DRIVER" => array(
		"NAME"		=> "serial",
		"OPTIONS"	=> array(
			"REPOSITORIES_FILEPATH"	=> "AJXP_INSTALL_PATH/server/conf/repo.ser",
			"USERS_DIRPATH"			=> "AJXP_INSTALL_PATH/server/users")
	),
	"AUTH_DRIVER" => array(
		"NAME"		=> "ld",
		"OPTIONS"	=> array(
			"LOGIN_REDIRECT"		=> false,
			"AUTOCREATE_AJXPUSER" 	=> false,
			"TRANSMIT_CLEAR_PASS"	=> true
		)
	),
	"LOG_DRIVER" => array(
	 	"NAME" => "text",
	 	"OPTIONS" => array(
	 		"LOG_PATH" 		=> "AJXP_INSTALL_PATH/server/logs/",
	 		"LOG_FILE_NAME"	=> 'log_' . date('m-d-y') . '.txt',
	 		"LOG_CHMOD"		=> 0770
	 	)
	),
	"ACTIVE_PLUGINS" => array("editor.*", "uploader.flex", "uploader.html", "gui.ajax", "hook.*")
);

if(AJXP_Utils::userAgentIsMobile()){
	$PLUGINS["ACTIVE_PLUGINS"][] = "gui.mobile";
}

$REPOSITORIES[0] = array(
	"DISPLAY"		=>	"Default Files",
	"DRIVER"		=>	"fs",
	"DRIVER_OPTIONS"=> array(
		"PATH"					=>	$application->getAbsolutePath() . '/files',
		"CREATE"				=>	true,
		"RECYCLE_BIN" 			=> 	'recycle_bin',
		"CHMOD_VALUE"   		=>  '0600',
		"DEFAULT_RIGHTS"		=>  "",
		"PAGINATION_THRESHOLD"	=> 500,
		"PAGINATION_NUMBER" 	=> 200,
		"META_SOURCES"			=> array()
	)
);

$REPOSITORIES[1] = array(
	"DISPLAY"		=>	"Settings",
	"DISPLAY_ID"	=>	"165",
	"DRIVER"		=>	"ajxp_conf",
	"DRIVER_OPTIONS"=> array(
	)
);

$default_language="en";

$AJXP_JS_DEBUG = false;
$AJXP_SERVER_DEBUG = false;

$upload_max_number = 16;
$upload_max_size_per_file = 0;
$upload_max_size_total = 0;

$welcomeCustomMessage = "";
$webmaster_email = "webmaster@yourdomain.com";
$use_https=false;
$max_caracteres=50;
$allowRealSizeProbing=false;
