<?php
/*
Plugin Name: LD package
Plugin URI: http://h6e.net/wordpress/plugins/ld-package
Description: Disable various update mechanisms & Plugins/Themes/Users panels
Version: 0.5.3
Author: h6e.net
Author URI: http://h6e.net/
*/

function ld_handle_capabilities()
{    
	global $wp_roles;
	if (empty($wp_roles)) {
		return;
	}

	$disable_admin_caps = array(
		// 'activate_plugins', 'edit_themes',
		'edit_plugins', 'install_plugins', 'delete_plugins', 'update_plugins',
		'install_themes', 'delete_themes', 'update_themes',
		'create_users', 'edit_users', 'delete_users',
		'update_core'
	);
	$disable_admin_caps = apply_filters('ld_disable_admin_caps', $disable_admin_caps);
	
	$enable_admin_caps = array(
		'activate_plugins',
		'edit_themes'
	);
	$enable_admin_caps = apply_filters('ld_enable_admin_caps', $enable_admin_caps);

	$ld_disabled_admin_caps = get_option('ld_disabled_admin_caps');
	if (empty($ld_disabled_admin_caps)) {
		$ld_disabled_admin_caps = $disable_admin_caps;
		update_option('ld_disabled_admin_caps', $ld_disabled_admin_caps);
	}

	$role = $wp_roles->get_role('administrator');
	if ($role) {
		foreach ($disable_admin_caps as $cap) {
			if ($role->has_cap($cap)) {
				$role->remove_cap($cap);
				$ld_disabled_admin_caps[] = $cap;
				update_option('ld_disabled_admin_caps', array_unique($ld_disabled_admin_caps));
			}
		}
		foreach ($enable_admin_caps as $cap) {
			if (!$role->has_cap($cap)) {
				$role->add_cap($cap);
			}
		}
	}
}

add_action('plugins_loaded', 'ld_handle_capabilities');

function ld_handle_registration()
{
	if (Zend_Registry::isRegistered('site')) {
		$site = Zend_Registry::get('site');
		$users_can_register = $site->getConfig('open_registration', 0);
		update_option('users_can_register', $users_can_register);
	}	
}

add_action('plugins_loaded', 'ld_handle_registration');

function ld_deactivate_plugin()
{
	$ld_disabled_admin_caps = (array)get_option('ld_disabled_admin_caps');

	global $wp_roles;
	$role = $wp_roles->get_role('administrator');
	if ($role) {
		foreach ($ld_disabled_admin_caps as $cap) {
			$role->add_cap($cap);
		}
	}
}

register_deactivation_hook('ld.php', 'ld_deactivate_plugin');

function ld_disable_admin_notices()
{
	remove_action('admin_notices', 'update_nag', 3);
	remove_action('admin_notices', 'akismet_warning');
}

add_action('admin_head', 'ld_disable_admin_notices');

function ld_disable_version_check()
{
	remove_action( 'init', 'wp_version_check' );

	$ld_plugin_update = apply_filters('ld_plugin_update', false);
	if (!$ld_plugin_update) {
		remove_action( 'load-plugins.php', 'wp_update_plugins' );
		remove_action( 'load-update.php', 'wp_update_plugins' );
		remove_action( 'load-update-core.php', 'wp_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'wp_update_plugins', 'wp_update_plugins' );
		wp_clear_scheduled_hook('wp_update_plugins');
	}

	$ld_theme_update = apply_filters('ld_theme_update', false);
	if (!$ld_theme_update) {
		remove_action( 'load-themes.php', 'wp_update_themes' );
		remove_action( 'load-update.php', 'wp_update_themes' );
		remove_action( 'load-update-core.php', 'wp_update_themes' );
		remove_action( 'admin_init', '_maybe_update_themes' );
		remove_action( 'wp_update_themes', 'wp_update_themes' );
		wp_clear_scheduled_hook('wp_update_themes');
	}
}

add_action('plugins_loaded', 'ld_disable_version_check');

function ld_admin_menu_before()
{
	remove_action('admin_menu', 'akismet_config_page');
	remove_action('admin_menu', 'akismet_stats_page');
}

add_action('init', 'ld_admin_menu_before', 102);

function ld_admin_menu()
{
	global $menu, $submenu;

	$disable_menus = array('plugins.php', 'users.php');
	$disable_menus = apply_filters('ld_disable_menus', $disable_menus);

	foreach ($menu as $key => $item) {
		$script = $item[2];
		if (!empty($disable_menus) && in_array($script, $disable_menus)) {
			unset($menu[$key]);
		}
	}

	$disable_submenus = array(
		'tools.php',
		'themes.php', 'theme-editor.php', 'theme-install.php',
		'update-core.php',
		'options-misc.php'
	);
	$disable_submenus = apply_filters('ld_disable_submenus', $disable_submenus);

	foreach ($submenu as $key => $sub) {
		foreach ($sub as $num => $item) {
			$script = $item[2];
			if (!empty($disable_submenus) && in_array($script, $disable_submenus)) {
				unset($submenu[$key][$num]);
			}
		}
	}
}

add_action('admin_menu', 'ld_admin_menu', 102);

/**
 * Don't load default widgets when interacting from Ld Installer.
 *
 * This is causing problem with the $wp_widget_factory global.
 */
function ld_load_default_widgets($default = true)
{
	if (defined('WP_LD_INSTALLER') && constant('WP_LD_INSTALLER')) {
		return false;
	}
	return $default;
}

add_filter('load_default_widgets', 'ld_load_default_widgets');

function ld_wp_dashboard_setup()
{
	global $wp_meta_boxes;

	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);

	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
}

add_action('wp_dashboard_setup', 'ld_wp_dashboard_setup', 102);

function ld_option_home($value)
{
	if (Zend_Registry::isRegistered('application')) {
		$application = Zend_Registry::get('application');
		$url = $application->getUrl();
		return substr($url, 0, -1);
	}
	return $value;
}

add_filter('option_home', 'ld_option_home');

function ld_option_siteurl($value)
{
	if (Zend_Registry::isRegistered('application')) {
		$application = Zend_Registry::get('application');
		if (method_exists($application, 'getAbsoluteUrl')) {
			$url = $application->getAbsoluteUrl();
			return substr($url, 0, -1);
		}
	}
	return $value;
}

add_filter('option_siteurl', 'ld_option_siteurl');

function ld_template_redirect()
{
	if ( is_404() ) {
		if ( !$requested_url ) {
			// build the URL in the address bar
			$requested_url  = is_ssl() ? 'https://' : 'http://';
			$requested_url .= $_SERVER['HTTP_HOST'];
			$requested_url .= $_SERVER['REQUEST_URI'];
		}
		$application = Zend_Registry::isRegistered('application') ? Zend_Registry::get('application') : null;
		if ($application && $application->isRoot() && strpos($requested_url, site_url()) !== false) {
			$redirect_url = str_replace(site_url(), home_url(), $requested_url);
			wp_redirect($redirect_url, 301);
			exit();
		}
	}
}

add_action('template_redirect', 'ld_template_redirect');

function ld_locale($locale = '')
{
	if (empty($locale) || $locale == 'auto') {
		return 'en_US';
	}
	return $locale;
}

add_filter('locale', 'ld_locale');

function ld_fix_globals()
{
	if (empty($GLOBALS['wp_rewrite'])) {
		$GLOBALS['wp_rewrite'] =& new WP_Rewrite();
	}
}

add_action('plugins_loaded', 'ld_fix_globals', 0);

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Wordpress:plugin');
}
