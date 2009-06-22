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
	echo '<link rel="stylesheet" type="text/css" href="' . $site->getUrl('css') . '/ld-ui/ld-bars.css' .'" />'."\n";
	echo '<style type="text/css"> #bbFoot { display:none; }</style>'."\n";
}

add_action('bb_get_admin_header', 'ld_admin_head');

function ld_template_head()
{
	$site = Zend_Registry::get('site');
	echo '<link rel="stylesheet" type="text/css" href="' . $site->getUrl('css') . '/ld-ui/ld-bars.css' .'" />'."\n";
	echo '<style type="text/css"> body { margin-bottom:50px; }</style>'."\n";
}

add_action('bb_head', 'ld_template_head');

function ld_footer()
{
	require_once 'Ld/Ui.php';
	Ld_Ui::super_bar();
}

add_action( 'bb_admin_footer', 'ld_footer' );

add_action( 'bb_foot', 'ld_footer' );

function ld_top_bar()
{
	?>
	<div class="h6e-top-bar">
		<div class="h6e-top-bar-inner">
			<div class="a">
				<?php bb_option('name'); ?>
			</div>
			<div class="b">
				<?php if ( !in_array( bb_get_location(), array( 'login-page', 'register-page' ) ) ) login_form(); ?>
			</div>
		</div>
	</div>
	<?php
}
