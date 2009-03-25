<?php

class Installer_Plugin_Openid extends Ld_Installer
{

	function install($preferences = array())
	{
		parent::install($preferences);
		
		global $wpdb, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem;
		global $wp_roles, $wpmu_version, $wp_locale;
		require_once $this->absolutePath . "/../../../wp-load.php";
		require_once $this->absolutePath . "/../../../wp-admin/includes/plugin.php";
		activate_plugin('openid/openid.php');
	}

}
