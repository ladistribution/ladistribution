<?php
/*
Plugin Name: Ld package
Plugin URI: http://h6e.net/bbpress/plugins/ld
Description: Disable various update mechanisms & Plugins/Themes/Users panels
Version: 0.4.1
Author: h6e
Author URI: http://h6e.net/
*/

function ld_bbpress_disable_menus()
{
	global $bb_menu, $bb_submenu;

	$disable_menus = array('plugins.php', 'users.php', 'tools-recount.php');
	foreach ($bb_menu as $key => $item) {
		$script = $item[2];
		if (!empty($disable_menus) && in_array($script, $disable_menus)) {
			unset($bb_menu[$key]);
		}
	}

	$disable_submenus = array('options-wordpress.php', 'bb_ksd_configuration_page');
	foreach ($bb_submenu as $key => $sub) {
		foreach ($sub as $num => $item) {
			$script = $item[2];
			if (!empty($disable_submenus) && in_array($script, $disable_submenus)) {
				unset($bb_submenu[$key][$num]);
			}
		}
	}
}

add_action('bb_admin_menu_generator', 'ld_bbpress_disable_menus', 20);

function ld_bbpress_repermalink_result($permalink)
{
	if (defined('LD_ROOT_CONTEXT') && constant('LD_ROOT_CONTEXT')) {
		$site = Zend_Registry::get('site');
		return 'http://' . $site->getHost() . $site->getPath() . '/';
	}
	return $permalink;
}

add_filter('bb_repermalink_result', 'ld_bbpress_repermalink_result');

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Bbpress:plugin');
}
