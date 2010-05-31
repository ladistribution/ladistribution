<?php

class Ld_Installer_Wordpress_Plugin_Hypercache extends Ld_Installer_Wordpress_Plugin
{

	public $plugin_file = 'hyper-cache/plugin.php';

	public function install($preferences = array())
	{
		$options = array(
			'comment' => 1,
			'archive' => 1,
			'timeout' => 1440,
			'clean_interval' => 60,
			'gzip' => 1,
			'store_compressed' => 0,
			'expire_type' => 'post',
			'lastmodified' => true,
			'reject_cookies' => "ld-auth"
		);
		$this->load_wp();
		update_option('hyper', $options);
		parent::install($preferences);
	}
	
	public function update()
	{
		// we can't active and deactivate in the same script
		// $this->load_wp();
		// deactivate_plugins($this->plugin_file);
		parent::update();
		// activate_plugin($this->plugin_file);
	}

	public function uninstall()
	{
		parent::uninstall();
	}

}
