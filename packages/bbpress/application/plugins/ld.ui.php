<?php
/*
Plugin Name: Ld UI
Plugin URI: http://h6e.net/bbpress/plugins/ld-ui
Description: Enable some La Distribution UI elements
Version: 0.5.5
Author: h6e
Author URI: http://h6e.net/
*/

function ld_bbpress_admin_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') .'" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl('bars') . '" />'."\n";
	echo '<style type="text/css"> #bbFoot { display:none; }</style>'."\n";
	?>
	<style type="text/css">
	<?php if (bb_get_option('topbar') != 'never') : ?>
	html, body { height:auto }
	body { padding-top:30px !important; }
	#bbHead { display:none; }
	</style>
	<?php endif ?>
	<?php
}

add_action('bb_get_admin_header', 'ld_bbpress_admin_head');

function ld_bbpress_template_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') .'" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl() . '" />'."\n";
	echo '<style type="text/css">' . "\n";
	if (ld_display_bar('topbar')) {
		echo 'body { padding-top:30px !important; }' . "\n";
	}
	if (ld_display_bar('super')) {
		echo 'body { margin-bottom:50px !important; }' . "\n";
	}
	$theme = bb_get_option('bb_active_theme');
	if ($theme == 'core#kakumei' || $theme == 'core#kakumei-blue' || $theme == 'core#kakumei-ld') {
		echo 'body { background:none; }' . "\n";
		if (ld_display_bar('topbar')) {
			echo '#header p.login { display:none; }' . "\n";
		}
	}
	echo '</style>' . "\n";
}

add_action('bb_head', 'ld_bbpress_template_head');

function ld_display_bar($bar = 'topbar')
{
	$topbar = bb_get_option($bar);
	if (empty($topbar) || $topbar == 'everyone' || ( $topbar == 'connected' && bb_is_user_logged_in() ) ) {
		return true;
	}
	return false;
}

function ld_bbpress_footer()
{
	if (ld_display_bar('topbar')) {
		Ld_Ui::topBar(array('full-width' => true));
	}
	if (ld_display_bar('superbar')) {
		Ld_Ui::superBar();
	}
}

function ld_bbpress_admin_footer()
{
	if (ld_display_bar('topbar')) {
		Ld_Ui::topBar(array('full-width' => true));
	}
	if (ld_display_bar('superbar')) {
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
