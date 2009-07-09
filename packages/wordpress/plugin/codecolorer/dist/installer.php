<?php

class Ld_Installer_Wordpress_Plugin_Codecolorer extends Ld_Installer
{

	public $plugin_file = 'codecolorer/codecolorer.php';

	private function load_wp()
	{
		if (empty($this->loaded)) {
			define('WP_LD_INSTALLER', true);
			global $wpdb, $wp_version, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem, $wp_roles;
			global $_wp_deprecated_widgets_callbacks;
			require_once $this->absolutePath . "/../../../wp-load.php";
			require_once $this->absolutePath . "/../../../wp-admin/includes/plugin.php";
			$this->loaded = true;
		}
	}

	public function install($preferences = array())
	{
		parent::install($preferences);
		$this->load_wp();
		activate_plugin($this->plugin_file);
	}

	public function uninstall()
	{
		$this->load_wp();
		deactivate_plugins($this->plugin_file);
		parent::uninstall();
	}

}
