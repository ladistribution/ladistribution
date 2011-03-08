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
?>
<script type='text/javascript' src='<?php echo  Ld_Ui::getJsUrl('/ld/ld.js', 'lib-admin') ?>'></script>
<?php
	if (ld_display_bar('topbar')) {
		echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'css-ld-ui') . '" />'."\n";
		if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
			echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl('bars') . '" />'."\n";
		}
	}
?>
<style type="text/css">
#dashboard_right_now a.button { display:none; }
<?php if (get_option('topbar') != 'never') : ?>
#wpcontent { padding-top:31px !important; }
#backtoblog { margin-top:31px !important; }
#wphead, #user_info { display:none; }
<?php endif ?>
</style>
<?php
}

add_action('admin_head', 'ld_admin_head', 1);

add_action('login_head', 'ld_admin_head');

function ld_styles()
{
	wp_enqueue_script('jquery', Ld_Ui::getJsUrl('/jquery/jquery.js', 'js-jquery'));
	wp_enqueue_script('ld', Ld_Ui::getJsUrl('/ld/ld.js', 'lib-admin'), array('jquery'));
	$current_theme = get_current_theme();
	if ($current_theme == 'Minimal' || $current_theme == 'Minimal (with blocks)') {
		wp_enqueue_style('h6e-minimal', Ld_Ui::getCssUrl('/h6e-minimal/h6e-minimal.css', 'css-h6e-minimal'));
	}
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
	if ($current_theme == 'Twenty Ten' || $current_theme == 'Coraline') {
		$colors = Ld_Ui::getApplicationColors();
		echo '#wrapper { border:1px solid #' . $colors['ld-colors-border-2'] . '; margin-bottom:30px; }' . "\n";
	}
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
}

add_action('wp_footer', 'ld_footer');

add_action('login_footer', 'ld_footer');

add_action('admin_footer', 'ld_footer');

add_action('login_form', 'ld_footer');

function ld_show_admin_bar($classes)
{
	return false;
}

add_filter('show_admin_bar', 'ld_show_admin_bar');
