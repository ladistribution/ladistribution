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
		$this->handleRewrite();
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
		$this->handleRewrite();
	}

	function postMove()
	{
		$this->handleRewrite();
	}

	function postUninstall()
	{
		if (defined('LD_NGINX') && constant('LD_NGINX')) {
			$nginxDir = $site->getDirectory('dist') . '/nginx';
			Ld_Files::rm($nginxDir . "/" . $this->getInstance()->getId() . ".conf", $conf);
		}
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
		if (defined('LD_NGINX') && constant('LD_NGINX')) {
			// Generate configuration
			$path = $this->getSite()->getPath() . '/' . $this->getPath() . '/';
			$nginxConf  = 'location {PATH} {' . "\n";
			$nginxConf .= '  rewrite ^{PATH}1.0  {PATH}sync/1.1/index.php$is_args$args last;' . "\n";
			$nginxConf .= '  rewrite ^{PATH}1.1  {PATH}sync/1.1/index.php$is_args$args last;' . "\n";
			$nginxConf .= '}' . "\n";
			$nginxConf = str_replace('{PATH}', $path, $nginxConf);
			// Write configuration
			$nginxDir = $this->getSite()->getDirectory('dist') . '/nginx';
			Ld_Files::ensureDirExists($nginxDir);
			Ld_Files::put($nginxDir . "/" . $this->getInstance()->getId() . ".conf", $nginxConf);
		}
	}

	function isVisible()
	{
		return Ld_Auth::isAuthenticated();
	}

}
