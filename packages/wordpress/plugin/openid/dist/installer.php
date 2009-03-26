<?php

class Installer_Plugin_Openid extends Ld_Installer
{

	function load_wp()
	{
		if (empty($this->loaded)) {

			global $wpdb, $wp_version, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem, $wp_roles;

			require_once $this->absolutePath . "/../../../wp-load.php";
			require_once $this->absolutePath . "/../../../wp-admin/includes/plugin.php";
			require_once $this->absolutePath . "/../../../wp-includes/capabilities.php";

			if ( ! isset( $wp_roles ) )
				$wp_roles = new WP_Roles();

			$this->loaded = true;

		}
	}

	function install($preferences = array())
	{
		parent::install($preferences);

		$this->load_wp();

		activate_plugin('openid/openid.php');
	}

}
