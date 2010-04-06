<?php

class Ld_Installer_Statusnet extends Ld_Installer
{

	public function postInstall()
	{
		$this->createHtaccess();
		$this->createTables();
	}

	public function createHtaccess()
	{
		if (defined('LD_REWRITE') && constant('LD_REWRITE') === true) {
			$path = $this->getSite()->getBasePath() . '/' . $this->getPath() . '/';
			$htaccess  = "RewriteEngine on\n";
			$htaccess .= "RewriteBase $path\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
			$htaccess .= "RewriteRule (.*) index.php?p=$1 [L,QSA]\n";
			$htaccess .= '<FilesMatch "\.(ini)">' . "\n";
			$htaccess .= 'Order allow,deny' . "\n";
			$htaccess .= '</FilesMatch>' . "\n";
			Ld_Files::put($this->getAbsolutePath() . "/.htaccess", $htaccess);
		}
	}

	public function createTables()
	{
		$con = $this->getInstance()->getDbConnection('php');
		$dbPrefix = $this->getInstance()->getDbPrefix();
		$schema = Ld_Files::get($this->getAbsolutePath() . '/db/statusnet.sql');
		$tables = explode(';', $schema);
		foreach ($tables as $table) {
			$table = str_replace("create table ", "create table $dbPrefix", $table);
			$con->query($table);
		}
	}

}
