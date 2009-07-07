<?php

class Ld_Installer_Wordpress_Plugin_Openid extends Ld_Installer
{

	public $plugin_file = 'openid/openid.php';

	private function load_wp()
	{
		if (empty($this->loaded)) {

			define('WP_LD_INSTALLER', true);

			global $wpdb, $wp_version, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem, $wp_roles;

			// fix 'Wrong datatype for second argument in wp-includes/widgets.php on lines 607'
			global $_wp_deprecated_widgets_callbacks;

			require_once $this->absolutePath . "/../../../wp-load.php";
			require_once $this->absolutePath . "/../../../wp-admin/includes/plugin.php";
			require_once $this->absolutePath . "/../../../wp-includes/capabilities.php";

			if ( ! isset( $wp_roles ) )
				$wp_roles = new WP_Roles();

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

		// this doesn't work right now ...
		// uninstall_plugin($this->plugin_file);

		parent::uninstall();
	}

}
