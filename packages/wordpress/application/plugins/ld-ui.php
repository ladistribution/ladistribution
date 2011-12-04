<?php
/*
Plugin Name: LD Ui
Plugin URI: http://h6e.net/wordpress/plugins#ld-ui
Description: Enable some La Distribution UI elements
Version: 0.6.20
Author: h6e.net
Author URI: http://h6e.net/
*/

function ld_admin_head()
{
?>
<script type='text/javascript' src='<?php echo  Ld_Ui::getJsUrl('/ld/ld.js', 'lib-admin') ?>'></script>
<?php
echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-bars.css', 'css-ld-ui') . '" />'."\n";
echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/wp-bars.css', 'css-ld-ui') . '" />'."\n";
if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl('bars') . '" />'."\n";
}
?>
<style type="text/css">
#dashboard_right_now a.button { display:none; }
<?php
if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
	ld_admin_bar_colors();
}
?>
</style>
<?php
}

function ld_login_head()
{
?>
<script type="text/javascript" src="<?php echo Ld_Ui::getJsUrl('/jquery/jquery.js', 'js-jquery') ?>"></script>
<script type='text/javascript' src='<?php echo  Ld_Ui::getJsUrl('/ld/ld.js', 'lib-admin') ?>'></script>
<?php
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/ld-bars.css', 'css-ld-ui') . '" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getCssUrl('/ld-ui/wp-bars.css', 'css-ld-ui') . '" />'."\n";
	if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
		echo '<link rel="stylesheet" type="text/css" href="' . Ld_Ui::getApplicationStyleUrl('bars') . '" />'."\n";
	}
}

add_action('admin_head', 'ld_admin_head', 1);

add_action('login_head', 'ld_login_head', 1);

function ld_enqueue_style($handle, $file, $package, $deps = array())
{
	$infos = Ld_Ui::getPackageInfos($package, 'css');
	$src = Ld_Ui::getSite()->getAbsoluteUrl('css') . $file;
	wp_enqueue_style($handle, $src, $deps, $infos['version']);
}

function ld_enqueue_script($handle, $file, $package, $deps = array())
{
	$infos = Ld_Ui::getPackageInfos($package, 'js');
	$src = Ld_Ui::getSite()->getAbsoluteUrl('js') . $file;
	wp_enqueue_script($handle, $src, $deps, $infos['version']);
}

function ld_enqueue_scripts()
{
	global $site, $application;
	// JS
	// wp_enqueue_script('jquery');
	// ld_enqueue_script('jquery', '/jquery/jquery.js', 'js-jquery');
	ld_enqueue_script('ld', '/ld/ld.js', 'lib-admin', array('jquery'));
	// CSS
	$current_theme = get_current_theme();
	if ($current_theme == 'Minimal' || $current_theme == 'Minimal (with blocks)') {
		ld_enqueue_style('h6e-minimal', '/h6e-minimal/h6e-minimal.css', 'css-h6e-minimal');
	}
	ld_enqueue_style('ld-ui', '/ld-ui/ld-ui.css', 'css-ld-ui');
	ld_enqueue_style('wp-bars', '/ld-ui/wp-bars.css', 'css-ld-ui');
	if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
		$colors = $application->getColors();
		$appearance_version = $site->getConfig('appearance_version');
		$version = substr(md5($appearance_version . serialize($colors)), 0, 10);
		$params = array(
			'module' => 'slotter', 'controller' => 'appearance',
			'action' => 'style', 'id' => $application->getId()
		);
		$url = $site->getAdmin()->buildAbsoluteUrl($params, 'default', false);
		wp_enqueue_style('ld-app-style', $url, array('ld-ui'), $version);
	}
}

add_action('wp_enqueue_scripts', 'ld_enqueue_scripts');

function ld_template_head()
{
	$current_theme = get_current_theme();
	echo '<style type="text/css">' . "\n";
	if (ld_display_bar('topbar')) {
		echo 'body { padding-top:30px !important; }' . "\n";
	}
	// if ($current_theme == 'Twenty Ten' || $current_theme == 'Coraline') {
	// 	$colors = Ld_Ui::getApplicationColors();
	// 	echo '#wrapper { border:1px solid #' . $colors['ld-colors-border-2'] . '; margin-bottom:30px; }' . "\n";
	// }
	// if ($current_theme == 'Twenty Eleven') {
	// 	$colors = Ld_Ui::getApplicationColors();
	// 	echo '#page { border:1px solid #' . $colors['ld-colors-border-2'] . '; margin-bottom:30px; }' . "\n";
	// }
	if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
		ld_admin_bar_colors();
	}
	echo '</style>'."\n";
}

add_action('wp_head', 'ld_template_head');

function ld_admin_bar_colors()
{
$colors = Ld_Ui::getApplicationColors();
?>
#wpadminbar {
  background-color:#<?php echo $colors['ld-colors-background-2'] ?>;
}

#wpadminbar *, #wpadminbar * a,
#wpadminbar .ab-top-menu > li > .ab-item,
#wpadminbar .ab-top-menu > li:hover > .ab-item,
#wpadminbar .ab-top-menu > li.hover > .ab-item,
#wpadminbar #adminbarsearch .adminbar-input {
  color:#<?php echo $colors['ld-colors-text-2'] ?>;
}

#wpadminbar #adminbarsearch .adminbar-input:-moz-placeholder {
  color:#<?php echo Ld_Ui::contrastColor($colors['ld-colors-text-2'], -50) ?>;
}

#wpadminbar,
#wpadminbar .quicklinks > ul > li,
#wpadminbar .quicklinks > ul.ab-top-secondary > li {
  border-color:#<?php echo $colors['ld-colors-border-2'] ?>;
}
<?php
}

function ld_display_bar($bar = 'topbar')
{
	// if wordpress admin bar is displayed ?
	if (Ld_Auth::isAuthenticated()) {
		return false;
	}

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

function ld_admin_bar_menu_before($wp_admin_bar)
{
    $site = Zend_Registry::get('site');
    $application = Zend_Registry::get('application');

    $wp_admin_bar->add_menu( array(
        'id' => 'ld-site',
        'title' => '<span>' . $site->getName() . '</span>',
        'href' => $site->getUrl(),
        'meta'  => array(
            'class' => 'ld-site-name',
        ),
    ) );
    $wp_admin_bar->add_menu( array(
        'id' => 'ld-application',
        'title' => '<span>' . $application->getName() . '</span>',
        'href' => $application->getUrl(),
        'meta'  => array(
            'class' => 'ld-app-name wordpress',
        ),
    ) );

    if (Ld_Auth::isAuthenticated() && $user = Ld_Auth::getUser()) {

        $wp_admin_bar->add_menu( array(
            'id'     => 'ld-logout',
            'parent' => 'top-secondary',
            'title'  => Ld_Translate::translate('Sign Out'),
            'href'   => wp_logout_url($_SERVER["REQUEST_URI"])
        ) );

        $name = empty($user['fullname']) ? $user['username'] : $user['fullname'];
        $avatar = Ld_Ui::getAvatar($user, 16) . ' ';

        $wp_admin_bar->add_menu( array(
            'id'     => 'ld-user',
            'parent' => 'top-secondary',
            'title'  => $avatar . ' <span>' . $name . '</span>',
            'href'   => Ld_Ui::getIdentityUrl($user)
        ) );

    }

}

function ld_admin_bar_menu_after($wp_admin_bar)
{
    $wp_admin_bar->remove_menu('wp-logo');
    $wp_admin_bar->remove_menu('site-name');
    $wp_admin_bar->remove_menu('my-account');
}

function ld_admin_bar_after()
{
    echo Ld_Ui::getTopMenu();
}

add_action( 'admin_bar_menu', 'ld_admin_bar_menu_before', 5);

add_action( 'admin_bar_menu', 'ld_admin_bar_menu_after', 200);

add_action( 'wp_after_admin_bar_render', 'ld_admin_bar_after', 1001 );
