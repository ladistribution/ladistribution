<?php

class Ld_Installer_Admin extends Ld_Installer
{

	function install($preferences = array())
	{
		parent::install($preferences);

		$this->create_htaccess();
	}

	function create_htaccess()
	{
		if (true === LD_REWRITE) {
			$path = $this->site->getBasePath() . '/' . $this->path . '/';
			$htaccess  = "RewriteEngine on\n";
			$htaccess .= "RewriteBase $path\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
			$htaccess .= "RewriteRule (.*) index.php\n";
			Ld_Files::put($this->absolutePath . "/.htaccess", $htaccess);
		}
	}

}