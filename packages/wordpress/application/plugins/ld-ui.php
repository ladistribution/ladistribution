<?php
/*
Plugin Name: LD Ui
Plugin URI: http://h6e.net/wordpress/plugins/ld-ui
Description: Enable some La Distribution UI elements
Version: 0.5.0
Author: h6e.net
Author URI: http://h6e.net/
*/

function ld_admin_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'css-ld-ui') . '" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getSiteStyleUrl('bars') . '" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl('bars') . '" />'."\n";
	?>
	<style type="text/css">
	#dashboard_right_now a.button[href='update-core.php'] { display:none; }
	<?php if (get_option('superbar') != 'never') : ?>
	#footer { display:none !important; }
	<?php endif ?>
	<?php if (get_option('topbar') != 'never') : ?>
	html, body { height:auto }
	body { padding-top:31px !important; }
	#wphead { display:none; }
	#user_info { display:none; }
	</style>
	<?php endif ?>
	<?php
}

add_action('admin_head', 'ld_admin_head');

function ld_template_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'css-ld-ui') . '" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getSiteStyleUrl() . '" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl() . '" />'."\n";
	echo '<style type="text/css">' . "\n";
	echo '.wp-pre-super-bar { ';
	echo 'height:38px; ';
	switch ( get_current_theme() ) {
		case 'Titan': echo 'background:#E7E1DE; '; break;
		case 'Journalist': echo 'background:#222; '; break;
	}
	echo '}' . "\n";
	echo '</style>'."\n";
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
	if (isset($_GET['preview'])) {
		return;
	}
	echo '<div class="wp-pre-super-bar"></div>';
	Ld_Ui::superBar();
}

function ld_admin_footer()
{
	if (get_option('topbar') != 'never') {
		Ld_Ui::topBar(array('full-width' => true));
	}
	if (get_option('superbar') != 'never') {
		Ld_Ui::superBar();
	}
}

add_action('wp_footer', 'ld_footer');

add_action('admin_footer', 'ld_admin_footer');

add_action('login_form', 'ld_footer');

function ld_body_class($classes)
{
    $classes[] = 'ld-layout';
    return $classes;
}

add_filter('body_class', 'ld_body_class');
