<?php
/*
Plugin Name: Ld UI
Plugin URI: http://h6e.net/bbpress/plugins/ld-ui
Description: Enable some La Distribution UI elements
Version: 0.2-26-1
Author: h6e
Author URI: http://h6e.net/
*/

function ld_admin_head()
{
	$site = Zend_Registry::get('site');
	echo '<link rel="stylesheet" type="text/css" href="' . $site->getUrl('css') . '/ld-ui/ld-ui.css' .'" />'."\n";
	echo '<style type="text/css"> #bbFoot { display:none; }</style>'."\n";
}

add_action('bb_get_admin_header', 'ld_admin_head');

function ld_template_head()
{
	$site = Zend_Registry::get('site');
	echo '<link rel="stylesheet" type="text/css" href="' . $site->getUrl('css') . '/ld-ui/ld-ui.css' .'" />'."\n";
	echo '<style type="text/css"> body { margin-bottom:50px; }</style>'."\n";
}

add_action('bb_head', 'ld_template_head');

function ld_footer()
{
	$superbar = bb_get_option('superbar');
	if ($superbar == 'never') {
		return;
	}
	if ($superbar == 'connected' && !bb_is_user_logged_in()) {
		return;
	}
	Ld_Ui::super_bar(array('jquery' => true));
}

function ld_admin_footer()
{
	$superbar = bb_get_option('superbar');
	if ($superbar == 'never') {
		return;
	}
	Ld_Ui::super_bar(array('jquery' => false));
}

add_action( 'bb_admin_footer', 'ld_admin_footer' );

add_action( 'bb_foot', 'ld_footer' );
