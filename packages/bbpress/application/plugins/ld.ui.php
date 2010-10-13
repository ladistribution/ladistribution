<?php
/*
Plugin Name: Ld UI
Plugin URI: http://h6e.net/bbpress/plugins/ld-ui
Description: Enable some La Distribution UI elements
Version: 0.5.0
Author: h6e
Author URI: http://h6e.net/
*/

function ld_bbpress_admin_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') .'" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getSiteStyleUrl('bars') . '" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl('bars') . '" />'."\n";
	echo '<style type="text/css"> #bbFoot { display:none; }</style>'."\n";
	?>
	<style type="text/css">
	<?php if (bb_get_option('topbar') != 'never') : ?>
	html, body { height:auto }
	body { padding-top:31px !important; }
	#bbHead { display:none; }
	</style>
	<?php endif ?>
	<?php
}

add_action('bb_get_admin_header', 'ld_bbpress_admin_head');

function ld_bbpress_template_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') .'" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getSiteStyleUrl() . '" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl() . '" />'."\n";
	echo '<style type="text/css"> body {  background:none; margin-bottom:50px; }</style>'."\n";
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
	Ld_Ui::superBar();
}

function ld_bbpress_admin_footer()
{
	if (bb_get_option('topbar') != 'never') {
		Ld_Ui::topBar(array('full-width' => true));
	}
	if (bb_get_option('superbar') != 'never') {
		Ld_Ui::superBar();
	}
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
