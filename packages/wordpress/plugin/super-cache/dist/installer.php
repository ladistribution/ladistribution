<?php

class Ld_Installer_Wordpress_Plugin_Supercache extends Ld_Installer_Wordpress_Plugin
{

	public $plugin_file = 'wp-super-cache/wp-cache.php';

	public function install($preferences = array())
	{
		parent::install($preferences);
		Ld_Files::copy($this->getDir() . 'plugins/ld-super-cache.php', $this->getAbsolutePath() . '/../ld-super-cache.php');
		wp_cache_delete('plugins', 'plugins');
		activate_plugin('ld-super-cache.php');
	}

	public function update()
	{
		Ld_Files::copy($this->getDir() . 'plugins/ld-super-cache.php', $this->getAbsolutePath() . '/../ld-super-cache.php');
		parent::update();
	}
	
	public function uninstall()
	{
		$file = Ld_Files::real($this->getAbsolutePath() . '/../ld-super-cache.php');
		parent::uninstall();
		wpsc_remove_marker(ABSPATH . '.htaccess', 'WPSuperCache');
		// todo: remove WP_CACHE from wp_config.php
		if (defined('WP_CONTENT_DIR')) {
			Ld_Files::unlink(WP_CONTENT_DIR . '/cache');
			Ld_Files::unlink(WP_CONTENT_DIR . '/advanced-cache.php');
			Ld_Files::unlink(WP_CONTENT_DIR . '/wp-cache-config.php');
		}
		deactivate_plugins('ld-super-cache.php');
		Ld_Files::unlink($file);
	}

}
