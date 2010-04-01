<?php
/*
Plugin Name: LD ui
Plugin URI: http://h6e.net/wordpress/plugins/ld-ui
Description: Enable some La Distribution UI elements
Version: 0.4.1
Author: h6e.net
Author URI: http://h6e.net/
*/

function ld_stylesheet($file, $package)
{
	$site = Zend_Registry::get('site');
	$infos = $site->getLibraryInfos($package);
	if ($infos['type'] == 'application') {
		$infos = $site->getInstance($infos['path'])->getInfos();
	}
	return $site->getUrl('css') . $file . '?v=' . $infos['version'];
}

function ld_admin_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . ld_stylesheet('/ld-ui/ld-ui.css', 'css-ld-ui') . '" />'."\n";
	echo '<style type="text/css"> #footer { display:none; }</style>'."\n";
}

add_action('admin_head', 'ld_admin_head');

function ld_template_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . ld_stylesheet('/ld-ui/ld-ui.css', 'css-ld-ui') . '" />'."\n";
	echo '<style type="text/css"> body { margin-bottom:50px; }</style>'."\n";
}

add_action('wp_head', 'ld_template_head');

add_action('login_head', 'ld_template_head');

function ld_footer()
{
	$superbar = get_option('superbar');
	if ($superbar == 'never') {
		return;
	}
	if ($superbar == 'connected' && !is_user_logged_in()) {
		return;
	}
	Ld_Ui::super_bar(array('jquery' => true));
}

function ld_admin_footer()
{
	$superbar = get_option('superbar');
	if ($superbar == 'never') {
		return;
	}
	Ld_Ui::super_bar(array('jquery' => false));
}

add_action('wp_footer', 'ld_footer');

add_action('admin_footer', 'ld_admin_footer');

add_action('login_form', 'ld_footer');
