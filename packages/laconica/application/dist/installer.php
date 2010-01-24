<?php

class Ld_Installer_Laconica extends Ld_Installer
{

	function install($preferences = array())
	{
		parent::install($preferences);

		$this->create_htaccess();

		$this->create_config_file($preferences);
	}

	function create_config_file($preferences)
	{
		$cfg = "<?php\n";
        
		$cfg .= 'require_once(dirname(__FILE__) . "/dist/prepend.php");' . "\n";

		$cfg .= '$config["site"]["name"] = "' . $preferences['title'] . '";' . "\n";

		$cfg .= '$config["db"]["database"] = ' .
			'sprintf("mysqli://%s:%s@%s/%s", $db["user"], $db["password"], $db["host"], $db["password"], $db["name"]);' . "\n";

		$cfg .= '$config["site"]["fancy"] = true;' . "\n";

		Ld_Files::put($this->absolutePath . "/config.php", $cfg);
	}

	function create_htaccess()
	{
		if (true === LD_REWRITE) {
			$path = $this->site->getBasePath() . '/' . $this->path . '/';
			$htaccess  = "RewriteEngine on\n";
			$htaccess .= "RewriteBase $path\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
			$htaccess .= "RewriteRule (.*) index.php?p=$1 [L,QSA]\n";
			$htaccess .= '<FilesMatch "\.(ini)">' . "\n";
			$htaccess .= 'Order allow,deny' . "\n";
			$htaccess .= '</FilesMatch>' . "\n";
			Ld_Files::put($this->absolutePath . "/.htaccess", $htaccess);
		}
	}

}
