<?php
/*
Plugin Name: LD Ui
Plugin URI: http://h6e.net/wordpress/plugins#ld-ui
Description: Enable some La Distribution UI elements
Version: 0.5.5
Author: h6e.net
Author URI: http://h6e.net/
*/

function ld_admin_head()
{
	if (get_option('topbar') != 'never' || get_option('superbar') != 'never') {
		echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'css-ld-ui') . '" />'."\n";
		if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
			echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl('bars') . '" />'."\n";
		}
	}
	?>
	<style type="text/css">
	#dashboard_right_now a.button { display:none; }
	<?php if (get_option('superbar') != 'never') : ?>
	#footer { display:none !important; }
	<?php endif ?>
	<?php if (get_option('topbar') != 'never') : ?>
	#wpcontent { padding-top:31px !important; }
	#backtoblog { margin-top:31px !important; }
	#wphead, #user_info { display:none; }
	<?php endif ?>
	</style>
	<?php
}

add_action('admin_head', 'ld_admin_head');

add_action('login_head', 'ld_admin_head');

function ld_styles()
{
	wp_enqueue_style('ld-ui', Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'css-ld-ui'));
	if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
		wp_enqueue_style('application-style', Ld_Ui::getApplicationStyleUrl(), array('ld-ui'));
	}
}

add_action('wp_head', 'ld_styles', 1);

function ld_template_head()
{
	$current_theme = get_current_theme();
	echo '<style type="text/css">' . "\n";
	if (ld_display_bar('topbar')) {
		echo 'body { padding-top:30px !important; }' . "\n";
	}
	echo '.wp-pre-super-bar { ';
	echo 'height:38px; ';
	switch ($current_theme) {
		case 'Titan': echo 'background:#E7E1DE; '; break;
		case 'Journalist': echo 'background:#222; '; break;
	}
	echo '}' . "\n";
	echo '</style>'."\n";
}

add_action('wp_head', 'ld_template_head');

function ld_display_bar($bar = 'topbar')
{
	if (isset($_GET['preview'])) {
		return false;
	}

	$display = get_option($bar);
	if (empty($display) || $display == 'everyone' || ($display == 'connected' && is_user_logged_in())) {
		return true;
	}

	return false;
}

function ld_footer()
{
	if (ld_display_bar('topbar')) {
		Ld_Ui::topBar(array(
			'loginUrl' => wp_login_url(), 'logoutUrl' => wp_logout_url($_SERVER["REQUEST_URI"])
		));
	}

	if (ld_display_bar('superbar')) {
		echo '<div class="wp-pre-super-bar"></div>';
		Ld_Ui::superBar();
	}
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

add_action('login_form', 'ld_admin_footer');

function ld_show_admin_bar($classes)
{
	return false;
}

add_filter('show_admin_bar', 'ld_show_admin_bar');
