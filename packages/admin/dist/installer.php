<?php

class Ld_Installer_Admin extends Ld_Installer
{

	public function postInstall($preferences = array())
	{
		if (!defined('LD_REWRITE') || constant('LD_REWRITE')) {
			$this->create_htaccess();
		}
	}

	public function postUpdate()
	{
		foreach ($this->getSite()->getRawRepositories() as $id => $repository) {
			if (isset($repository['endpoint'])) {
				// upgrade old/deprecated releases
				$old_releases = array('barbes', 'concorde', 'danube');
				foreach ($old_releases as $release) {
					if (strpos($repository['endpoint'], LD_SERVER . 'repositories/' . $release) !== false) {
						$repository['endpoint'] = str_replace(
							LD_SERVER . 'repositories/' . $release,
							LD_SERVER . 'repositories/' . LD_RELEASE,
							$repository['endpoint']
						);
						$this->getSite()->getModel('repositories')->update($id, $repository);
					}
				}
			}
		}
	}

	public function postMove()
	{
		if (!defined('LD_REWRITE') || constant('LD_REWRITE')) {
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

	public $roles = array('user', 'admin');

	public $defaultRole = 'user';

	public function getRoles()
	{
		return $this->roles;
	}

}
