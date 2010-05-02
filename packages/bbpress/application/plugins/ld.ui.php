<?php
/*
Plugin Name: Ld UI
Plugin URI: http://h6e.net/bbpress/plugins/ld-ui
Description: Enable some La Distribution UI elements
Version: 0.4.1
Author: h6e
Author URI: http://h6e.net/
*/

function ld_bbpress_admin_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') .'" />'."\n";
	echo '<style type="text/css"> #bbFoot { display:none; }</style>'."\n";
}

add_action('bb_get_admin_header', 'ld_bbpress_admin_head');

function ld_bbpress_template_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') .'" />'."\n";
	echo '<style type="text/css"> body { margin-bottom:50px; }</style>'."\n";
}

add_action('bb_head', 'ld_bbpress_template_head');

function ld_bbpress_footer()
{
	$superbar = bb_get_option('superbar');
	if ($superbar == 'never') {
		return;
	}
	if ($superbar == 'connected' && !bb_is_user_logged_in()) {
		return;
	}
	Ld_Ui::superBar(array('jquery' => true));
}

function ld_bbpress_admin_footer()
{
	$superbar = bb_get_option('superbar');
	if ($superbar == 'never') {
		return;
	}
	Ld_Ui::superBar(array('jquery' => false));
}

add_action( 'bb_admin_footer', 'ld_bbpress_admin_footer' );

add_action( 'bb_foot', 'ld_bbpress_footer' );

if ( !function_exists( 'bb_get_avatar' ) ) :
function bb_get_avatar( $id_or_email, $size = 80, $default = '' )
{
	$src = Ld_Ui::getDefaultAvatarUrl($size);
	$src = Ld_Plugin::applyFilters('Bbpress:getAvatarUrl', $src, $id_or_email, $size, $default);
	$class = '';
	$avatar = '<img alt="" src="' . $src . '" class="' . $class . '" style="height:' . $size . 'px; width:' . $size . 'px;" />';
	return $avatar;
}
endif;
