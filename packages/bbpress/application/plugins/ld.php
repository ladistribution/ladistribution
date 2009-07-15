<?php
/*
Plugin Name: Ld package
Plugin URI: http://h6e.net/bbpress/plugins/ld
Description: Disable various update mechanisms & Plugins/Themes/Users panels
Version: 0.2-29-1
Author: h6e
Author URI: http://h6e.net/
*/

function ld_disable_menus()
{
	global $bb_menu, $bb_submenu;

	$disable_menus = array('plugins.php', 'users.php', 'tools-recount.php');
	foreach ($bb_menu as $key => $item) {
		$script = $item[2];
		if (!empty($disable_menus) && in_array($script, $disable_menus)) {
			unset($bb_menu[$key]);
		}
	}

	$disable_submenus = array('options-wordpress.php');
	foreach ($bb_submenu as $key => $sub) {
		foreach ($sub as $num => $item) {
			$script = $item[2];
			if (!empty($disable_submenus) && in_array($script, $disable_submenus)) {
				unset($bb_submenu[$key][$num]);
			}
		}
	}
}

add_action('bb_admin_menu_generator', 'ld_disable_menus');
