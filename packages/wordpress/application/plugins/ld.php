<?php
/*
Plugin Name: LD package
Plugin URI: http://h6e.net/wordpress/plugins/ld-package
Description: Disable various update mechanisms & Plugins/Themes/Users panels
Version: 0.2a
Author: h6e
Author URI: http://h6e.net/
*/

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

	$disable_submenus = array('tools.php', 'themes.php', 'theme-editor.php', 'update-core.php');
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
