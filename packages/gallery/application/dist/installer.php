<?php

class Ld_Installer_Gallery extends Ld_Installer
{

	public function postInstall($preferences = array())
	{
		defined("DOCROOT") or define("DOCROOT", $this->getAbsolutePath() . "/");
		defined("VARPATH") or define("VARPATH", DOCROOT . "var/");
		defined("SYSPATH") or define("SYSPATH", "DEFINED_TO_SOMETHING_SO_THAT_WE_CAN_KEEP_CONSISTENT_PREAMBLES_IN_THE_INSTALLER");

		require_once($this->getAbsolutePath() . '/installer/installer.php');

		$databases = $this->getSite()->getDatabases();
		$db = $databases[ $this->getInstance()->getDb() ];
		$dbPrefix = $this->getInstance()->getDbPrefix();

		$config = array(
			"host" 		=> $db['host'],
			"user"		=> $db['user'],
			"password"	=> $db['password'],
			"dbname"	=> $db['name'],
			"prefix"	=> $dbPrefix,
			"type"		=> "mysqli"
		);

		installer::connect($config);
		installer::select_db($config);
		installer::db_empty($config);
		installer::unpack_var();
		installer::unpack_sql($config);

		if (isset($preferences['administrator'])) {
			$username = $preferences['administrator']['username'];
			$this->setUserRoles(array($username => 'administrator'));
		}

		$con = $this->instance->getDbConnection();

		installer::create_private_key($config);

		$sql = installer::prepend_prefix($config["prefix"],
			"DELETE FROM {users} WHERE `id` = 2");
		$result = $con->query($sql);
		if (!$result) {
			throw Exception(mysql_error());
		}

		$sql = installer::prepend_prefix($config["prefix"],
			"INSERT INTO {modules} SET `active` = 1,  `name` = 'ld', `version` = '1'");
		$result = $con->query($sql);
		if (!$result) {
			throw Exception(mysql_error());
		}

		$blocks_dashboard_center = array(
			7 => array('gallery', 'photo_stream')
		);
		$sql = installer::prepend_prefix($config["prefix"], sprintf(
			"UPDATE {vars} SET value = '%s' WHERE module_name = 'gallery' AND name = 'blocks_dashboard_center'", 
			serialize($blocks_dashboard_center)
		));
		$result = $con->query($sql);

		$blocks_dashboard_sidebar = array(
			2 => array('gallery', 'block_adder'),
			3 => array('gallery', 'stats')
		);
		$sql = installer::prepend_prefix($config["prefix"], sprintf(
			"UPDATE {vars} SET value = '%s' WHERE module_name = 'gallery' AND name = 'blocks_dashboard_sidebar'", 
			serialize($blocks_dashboard_sidebar)
		));
		$result = $con->query($sql);

		$this->updateHtaccess();
	}

	public function postUpdate()
	{
		// upgrade code there
		// manual upgrade = http://example.com/gallery3/index.php/upgrader

		$this->updateHtaccess();
	}

	public $roles = array('user', 'administrator');

	public $defaultRole = 'user';

	public function getRoles()
	{
		return $this->roles;
	}

	public function updateHtaccess()
	{
		if (constant('LD_REWRITE')) {
			$path = $this->getSite()->getBasePath() . '/' . $this->getPath();
			$htaccess = Ld_Files::get($this->getAbsolutePath() . "/.htaccess");
			$htaccess .= "<IfModule mod_rewrite.c>\n";
			$htaccess .= "RewriteEngine on\n";
			$htaccess .= "RewriteBase {$path}\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
			$htaccess .= "RewriteRule ^(.*)$ index.php?kohana_uri=$1 [QSA,PT,L]\n";
			$htaccess .= "RewriteRule ^$ index.php?kohana_uri=$1 [QSA,PT,L]\n";
			$htaccess .= "</IfModule>";
			Ld_Files::put($this->getAbsolutePath() . "/.htaccess", $htaccess);
		}
	}

}
