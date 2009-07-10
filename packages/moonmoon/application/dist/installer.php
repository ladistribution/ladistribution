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

		$planet_config = new PlanetConfig($config);
		Ld_Files::put($this->absolutePath . '/custom/config.yml', $planet_config->toYaml());

		Ld_Files::put($this->absolutePath . '/admin/inc/pwd.inc.php',
			'<?php $login="admin"; $password="' .  md5($preferences['admin_password']) . '"; ?>'
		);

	}

}
