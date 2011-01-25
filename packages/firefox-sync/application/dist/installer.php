<?php

class Ld_Installer_FirefoxSync extends Ld_Installer
{

	public $colorSchemes = array('base', 'bars', 'panels');

	function postInstall($preferences = array())
	{
		$db = $this->instance->getDbConnection();
		foreach ($this->getSchemaTables() as $table) {
			$db->query($table);
		}
		$this->writeHtaccess();
	}

	function postUpdate()
	{
		$db = $this->instance->getDbConnection();
		$dbPrefix = $this->getInstance()->getDbPrefix();
		try {
			$db->query("ALTER TABLE `{$dbPrefix}wbo` add column ttl int");
			$db->query("ALTER TABLE `{$dbPrefix}wbo` add index ttl_idx(ttl)");
		} catch (Exception $e) {
			// column, index, alread exists
		}
		$this->writeHtaccess();
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
			`ttl` int NOT NULL,
			PRIMARY KEY  (`username`,`collection`,`id`),
			KEY `modified` (`username`,`collection`,`modified`),
			KEY `ttl` (`ttl`)
		) ENGINE=InnoDB;";

		return $tables;
	}

	function writeHtaccess()
	{
		if (constant('LD_REWRITE')) {
			$path = $this->getSite()->getBasePath() . '/' . $this->getPath() . '/';
			$htaccess  = "RewriteEngine on\n";
			$htaccess .= "RewriteBase {$path}\n";
			$htaccess .= "RewriteRule ^1.0 sync/1.1/index.php [E=AUTHORIZATION:%{HTTP:Authorization},L]\n";
			$htaccess .= "RewriteRule ^1.1 sync/1.1/index.php [E=AUTHORIZATION:%{HTTP:Authorization},L]\n";
			Ld_Files::put($this->getAbsolutePath() . "/.htaccess", $htaccess);
		}
	}

	function isVisible()
	{
		return Ld_Auth::isAuthenticated();
	}

}
