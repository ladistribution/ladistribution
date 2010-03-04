<?php

class Ld_Installer_Weave extends Ld_Installer
{

	function postInstall($preferences = array())
	{
		$db = $this->instance->getDbConnection();
		foreach ($this->getSchemaTables() as $table) {
			$db->query($table);
		}
		$this->createHtaccess();
	}

	function postUpdate($preferences = array())
	{
		$db = $this->instance->getDbConnection();
		$dbPrefix = $this->getInstance()->getDbPrefix();

		$db->query("ALTER TABLE `{$dbPrefix}wbo` CHANGE `username` `username` VARCHAR( 32 ) NOT NULL");
		$db->query("ALTER TABLE `{$dbPrefix}collections` CHANGE `userid` `userid` VARCHAR( 32 ) NOT NULL");
	}

	function getSchemaTables()
	{
		$dbPrefix = $this->getInstance()->getDbPrefix();

		$tables = array();

		// $tables[] = "CREATE TABLE `{$dbPrefix}users` (
		// 	id int(11) NOT NULL PRIMARY KEY auto_increment,
		// 	username varbinary(32) NOT NULL,
		// 	md5 varbinary(32) default NULL,
		// 	email varbinary(64) default NULL,
		// 	status tinyint(4) default '1',
		// 	alert text,
		// 	reset varchar(32)
		// ) ENGINE=InnoDB;";

		$tables[] = "CREATE TABLE `{$dbPrefix}collections` (
			`userid` varchar(32) NOT NULL,
			`collectionid` smallint(6) NOT NULL,
			`name` varchar(32) NOT NULL,
			PRIMARY KEY  (`userid`,`collectionid`),
			KEY `nameindex` (`userid`,`name`)
		) ENGINE=InnoDB;";

		$tables[] = "CREATE TABLE `{$dbPrefix}wbo` (
			`username` varchar(32) NOT NULL,
			`collection` smallint(6) NOT NULL default '0',
			`id` varbinary(64) NOT NULL default '',
			`parentid` varbinary(64) default NULL,
			`predecessorid` varbinary(64) default NULL,
			`sortindex` int(11) default NULL,
			`modified` bigint(20) default NULL,
			`payload` longtext,
			`payload_size` int(11) default NULL,
			PRIMARY KEY  (`username`,`collection`,`id`),
			KEY `parentindex` (`username`,`collection`,`parentid`),
			KEY `modified` (`username`,`collection`,`modified`),
			KEY `weightindex` (`username`,`collection`,`sortindex`),
			KEY `predecessorindex` (`username`,`collection`,`predecessorid`),
			KEY `size_index` (`username`,`payload_size`)
		) ENGINE=InnoDB;";
	
		return $tables;
	}

	function createHtaccess()
	{
		if (constant('LD_REWRITE')) {
			$path = $this->getSite()->getBasePath() . '/' . $this->getPath() . '/';
			$htaccess  = "RewriteEngine on\n";
			$htaccess .= "RewriteBase {$path}\n";
			// $htaccess .= "RewriteRule ^user/1.0 registration/1.0/index.php [L]\n";
			// $htaccess .= "RewriteRule ^user/1 registration/1.0/index.php [L]\n";
			// $htaccess .= "RewriteRule ^misc/1/captcha_html registration/1.0/captcha.php [L]\n";
			$htaccess .= "RewriteRule ^1.0 sync/1.0/index.php [L]";
			Ld_Files::put($this->getAbsolutePath() . "/.htaccess", $htaccess);
		}
	}

	function uninstall()
	{
		$db = $this->instance->getDbConnection();
		$dbPrefix = $this->instance->getDbPrefix();

		$db->query("DROP TABLE IF EXISTS {$dbPrefix}users");
		$db->query("DROP TABLE IF EXISTS {$dbPrefix}collections");
		$db->query("DROP TABLE IF EXISTS {$dbPrefix}wbo");

		parent::uninstall();
	}

}
