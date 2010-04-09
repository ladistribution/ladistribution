<?php

class Ld_Installer_Statusnet extends Ld_Installer
{

	public function postInstall($preferences = array())
	{
		$this->writeHtaccess();
		$this->createTables();
		if (isset($preferences['theme'])) {
			$this->setConfiguration(array('theme' => $preferences['theme']));
			$this->setTheme($preferences['theme']);
		}
	}

	public function postUpdate()
	{
	}

	public function postMove()
	{
		$this->writeHtaccess();
	}

	/* Configuration */

	public function getConfiguration()
	{
		$configuration = parent::getConfiguration();
		$configuration['name'] = $this->getInstance()->getName();
		if (empty($configuration['title'])) {
			$configuration['title'] = $configuration['name'];
		}
		return $configuration;
	}

	public function setConfiguration($configuration = array())
	{
		$instance = $this->getInstance();
		if (isset($configuration['name']) && isset($instance)) {
			$instance->setInfos(array('name' => $configuration['name']))->save();
		}
		return parent::setConfiguration($configuration);
	}

	/* Themes */

	public function getThemes()
	{
		$themes = array();

		$configuration = $this->getConfiguration();
		$themesDirectories = Ld_Files::getDirectories($this->getAbsolutePath() . '/theme');
		
		foreach ($themesDirectories as $id) {
			$name = $id;
			$dir = $this->getAbsolutePath() . '/theme/' . $id;
			$active = isset($configuration['theme']) && $configuration['theme'] == $id;
			$screenshot = null;
			$themes[$id] = compact('name', 'dir', 'screenshot', 'active');
		}

		return $themes;
	}

	public function setTheme($theme)
	{
		$themes = $this->getThemes();
		if (isset($themes[$theme])) {
			$this->setConfiguration(array('theme' => $theme));
		}
		return $theme;
	}

	/* Backup / Restore */

	public function getBackupDirectories()
	{
		parent::getBackupDirectories();
		foreach (array('avatar', 'background', 'file', 'local') as $folder) {
			$this->_backupDirectories[$folder] = $this->getAbsolutePath() . "/$folder/";
		}
		return $this->_backupDirectories;
	}

	public function restore($archive)
	{
		parent::restore($archive);

		foreach (array('avatar', 'background', 'file', 'local') as $folder) {
			if (file_exists($this->getRestoreFolder() . "/$folder")) {
				Ld_Files::copy($this->getRestoreFolder() . "/$folder", $this->getAbsolutePath() . "/$folder");
			}
		}

		Ld_Files::unlink($this->getRestoreFolder());
	}

	public function writeHtaccess()
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
		Ld_Files::unlink($this->getAbsolutePath() . "/db");
	}

}
