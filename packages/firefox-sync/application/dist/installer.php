<?php

class Ld_Installer_FirefoxSync extends Ld_Installer
{

	function postInstall($preferences = array())
	{
		$db = $this->instance->getDbConnection();
		foreach ($this->getSchemaTables() as $table) {
			$db->query($table);
		}
		$this->writeHtaccess();
	}

	function postUpdate($preferences = array())
	{
		$db = $this->instance->getDbConnection();
		$dbPrefix = $this->getInstance()->getDbPrefix();

		$db->query("ALTER TABLE `{$dbPrefix}wbo` CHANGE `username` `username` VARCHAR( 32 ) NOT NULL");
		$db->query("ALTER TABLE `{$dbPrefix}collections` CHANGE `userid` `userid` VARCHAR( 32 ) NOT NULL");
	}

	function postMove()
	{
		$this->writeHtaccess();
	}

	function getSchemaTables()
	{
		$dbPrefix = $this->getInstance()->getDbPrefix();

		$tables = array();

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

	function writeHtaccess()
	{
		if (constant('LD_REWRITE')) {
			$path = $this->getSite()->getBasePath() . '/' . $this->getPath() . '/';
			$htaccess  = "RewriteEngine on\n";
			$htaccess .= "RewriteBase {$path}\n";
			$htaccess .= "RewriteRule ^1.0 sync/1.0/index.php [L]";
			Ld_Files::put($this->getAbsolutePath() . "/.htaccess", $htaccess);
		}
	}

}
