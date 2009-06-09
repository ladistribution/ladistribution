<?php

class Ld_Installer_Admin extends Ld_Installer
{

	public function install($preferences = array())
	{
		parent::install($preferences);
        
		if (constant('LD_REWRITE')) {
			$this->create_htaccess();
		}
	}

	private function create_htaccess()
	{
		$path = $this->site->getBasePath() . '/' . $this->path . '/';
		$htaccess  = "RewriteEngine on\n";
		$htaccess .= "RewriteBase $path\n";
		$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
		$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
		$htaccess .= "RewriteRule (.*) index.php\n";
		Ld_Files::put($this->absolutePath . "/.htaccess", $htaccess);
	}

	public $roles = array('admin', 'user');

	public $defaultRole = 'user';

	public function getRoles()
	{
		return $this->roles;
	}

}