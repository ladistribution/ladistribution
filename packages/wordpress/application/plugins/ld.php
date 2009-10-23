<?php
/*
Plugin Name: LD package
Plugin URI: http://h6e.net/wordpress/plugins/ld-package
Description: Disable various update mechanisms & Plugins/Themes/Users panels
Version: 0.2-26-1
Author: h6e
Author URI: http://h6e.net/
*/

register_activation_hook('ld.php', 'ld_activate_plugin');

register_deactivation_hook('ld.php', 'ld_deactivate_plugin');

function ld_get_disabled_admin_capabilities()
{
	$capabilities = array(
		// plugins
		// 'activate_plugins',
		'edit_plugins', 'install_plugins', 'delete_plugins', 'update_plugins',
		// themes
		// 'edit_themes',
		'install_themes', 'delete_themes', 'update_themes',
		// users
		'create_users', 'edit_users', 'delete_users'
	);
	return $capabilities;
}

function ld_activate_plugin()
{
	global $wp_roles;
	$role = $wp_roles->get_role('administrator');
	if ($role) {
		foreach (ld_get_disabled_admin_capabilities() as $cap) {
			$role->remove_cap($cap);
		}
	}
}

function ld_deactivate_plugin()
{
	global $wp_roles;
	$role = $wp_roles->get_role('administrator');
	if ($role) {
		foreach (ld_get_disabled_admin_capabilities() as $cap) {
			$role->add_cap($cap);
		}
	}
}

function ld_disable_update_nag()
{
	remove_action('admin_notices', 'update_nag', 3);
}

add_action('admin_head', 'ld_disable_update_nag');

function ld_disable_version_check()
{
	remove_action( 'init', 'wp_version_check' );

	remove_action( 'load-plugins.php', 'wp_update_plugins' );
	remove_action( 'load-update.php', 'wp_update_plugins' );
	remove_action( 'admin_init', '_maybe_update_plugins' );
	remove_action( 'wp_update_plugins', 'wp_update_plugins' );
	
	remove_action( 'load-themes.php', 'wp_update_themes' );
	remove_action( 'load-update.php', 'wp_update_themes' );
	remove_action( 'admin_init', '_maybe_update_themes' );
	remove_action( 'wp_update_themes', 'wp_update_themes' );
	
	wp_clear_scheduled_hook('wp_update_plugins');
	wp_clear_scheduled_hook('wp_update_themes');
}

add_action('plugins_loaded', 'ld_disable_version_check');

function ld_disable_menus()
{
	global $menu, $submenu;

	$disable_menus = array('plugins.php', 'users.php');
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
	foreach ($submenu as $key => $sub) {
		foreach ($sub as $num => $item) {
			$script = $item[2];
			if (!empty($disable_submenus) && in_array($script, $disable_submenus)) {
				unset($submenu[$key][$num]);
			}
		}
	}
}

add_action('admin_menu', 'ld_disable_menus');

/**
 * Don't load default widgets when interacting from Ld Installer.
 *
 * This was causing problem with the $wp_widget_factory global.
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

add_action('wp_dashboard_setup', 'ld_wp_dashboard_setup');

function ld_option_home($value)
{
	if (defined('LD_ROOT_CONTEXT') && constant('LD_ROOT_CONTEXT')) {
		$site = Zend_Registry::get('site');
		return 'http://' . $site->getHost() . $site->getPath();
	}
	return $value;
}

add_filter('option_home', 'ld_option_home');

function ld_loginurl($login_url = '', $redirect = '')
{
	$ld_login_url = Ld_Ui::getAdminUrl(array(
		'module' => 'default', 'controller' => 'auth', 'action' => 'login',
		'referer' => empty($redirect) ? null : urlencode($redirect)
	));
	if ($ld_login_url) {
	    return $ld_login_url;
	}
	return $login_url;
}

add_filter('login_url', 'ld_loginurl');

// function ld_logouturl($logout_url = '', $redirect = '')
// {
//  $logout_url = Ld_Ui::getAdminUrl(array(
//      'module' => 'default', 'controller' => 'auth', 'action' => 'logout',
//      'referer' => empty($redirect) ? null : urlencode($redirect)
//  ));
//  return $logout_url;
// }
// 
// add_filter('logout_url', 'ld_logouturl');

function ld_locale($locale = '')
{
    if (empty($locale) || $locale == 'auto') {
        return 'en_US';
    }
    return $locale;
}

add_filter('locale', 'ld_locale');
