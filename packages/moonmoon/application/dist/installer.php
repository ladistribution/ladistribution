<?php

class Ld_Installer_Moonmoon extends Ld_Installer
{

	public function install($preferences = array())
	{

		parent::install($preferences);

		require_once($this->absolutePath . '/app/classes/Planet.class.php');

		$config = array(
			'url' => 'readonly',
			'name' => $preferences['title'],
			'items' => 10,
			'shuffle' => 0,
			'refresh' => 240,
			'cache' => 10,
			'nohtml' => 0,
			'postmaxlength' => 0,
			'cachedir' => './cache'
		);

		$this->setConfiguration($config);

		if (isset($preferences['administrator'])) {
			$username = $preferences['administrator']['username'];
			$this->setUserRoles(array($username => 'administrator'));
		}
		
		$htaccess = 'php_value auto_prepend_file "dist/prepend.php"';
		Ld_Files::put($this->getAbsolutePath() . '/.htaccess', $htaccess);
		
		$htaccess = 'php_value auto_prepend_file none';
		Ld_Files::put($this->getAbsolutePath() . '/admin/.htaccess', $htaccess);
	}
    
	public function update()
	{
		Ld_Files::unlink($this->getDir() . 'application/custom/people.opml');

		parent::update();
	}

	public function getConfiguration()
	{
		require_once($this->getAbsolutePath() . '/app/classes/Planet.class.php');
		$config = Spyc::YAMLLoad($this->getAbsolutePath() . '/custom/config.yml');
		return $config;
	}

	public function setConfiguration($config)
	{
		$config = array_merge($this->getConfiguration(), $config);
		require_once($this->getAbsolutePath() . '/app/classes/Planet.class.php');
		$planet_config = new PlanetConfig($config);
		Ld_Files::put($this->getAbsolutePath() . '/custom/config.yml', $planet_config->toYaml());
		if (isset($config['name']) && isset($this->instance)) {
			$this->instance->setInfos(array('name' => $config['name']))->save();
		}
		return $config;
	}

	public $roles = array('administrator', 'visitor');

	public $defaultRole = 'visitor';

	public function getRoles()
	{
		return $this->roles;
	}

	public function getBackupDirectories()
	{
		parent::getBackupDirectories();
		$this->_backupDirectories['custom'] = $this->getAbsolutePath() . '/custom/';
		return $this->_backupDirectories;
	}

	public function restore($filename, $absolute = false)
	{
		parent::restore($filename, $absolute);
		Ld_Files::copy($this->getBackupFolder() . '/custom', $this->getAbsolutePath() . '/custom');
		Ld_Files::unlink($this->getBackupFolder());
	}

	// public function getCurrentTheme()
	// {
	// 	$conf  = $this->getConfiguration();
	// 	$template = isset($conf['template']) ? $conf['template'] : 'default';
	// 	return $template;
	// }

	// public function setTheme($theme)
	// {
	// 	$this->setConfiguration(array('template' => $theme));
	// }

	// public function getThemes()
	// {
	// 	$template = $this->getCurrentTheme();
	// 	$dirs = Ld_Files::getDirectories($this->getAbsolutePath() . '/custom/views/', array('archive', 'rss10', 'atom10'));
	// 	$themes = array();
	// 	foreach ($dirs as $id) {
	// 		$themes[$id] = array(
	// 			'name' => $id,
	// 			'active' => ($template == $id)
	// 		);
	// 	}
	// 	return $themes;
	// }

}
