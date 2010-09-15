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

	public function postMove()
	{
		if (constant('LD_REWRITE')) {
			$this->create_htaccess();
		}
	}

	private function create_htaccess()
	{
		$path = $this->getSite()->getPath() . '/' . $this->getPath() . '/';
		$htaccess  = "RewriteEngine on\n";
		$htaccess .= "RewriteBase $path\n";
		$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
		$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
		$htaccess .= "RewriteRule !\.(js|ico|gif|jpg|png|css|swf|php|txt)$ index.php\n";
		Ld_Files::put($this->getAbsolutePath() . "/.htaccess", $htaccess);
	}

	public function getLinks()
	{
		$baseUrl = $this->getSite()->getBaseUrl() . $this->getPath();
		if (constant('LD_REWRITE') == false) {
			$baseUrl .= '/index.php';
		}

		$links = $this->getManifest()->getLinks();
		foreach ($links as $id => $link) {
			$links[$id]['href'] = $baseUrl . $link['href'];
		}
		return $links;
	}

	public $roles = array('admin', 'user');

	public $defaultRole = 'user';

	public function getRoles()
	{
		return $this->roles;
	}

}