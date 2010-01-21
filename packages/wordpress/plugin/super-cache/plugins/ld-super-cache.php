<?php
/*
Plugin Name: LD Super Cache
Plugin URI: http://h6e.net/wordpress/plugins/ld-super-cache
Description: Tweak super cache with plugin hooks
Version: 0.3
Author: h6e.net
Author URI: http://h6e.net/
*/

function ld_super_cache_init()
{
	global $wp_supercache_actions;
	unset($wp_supercache_actions['cache_admin_page']);
	remove_action( 'admin_notices', 'wp_cache_admin_notice' );
	remove_action( 'after_plugin_row', 'wp_cache_plugin_notice' );
}

add_action('init', 'ld_super_cache_init');

function ld_super_cache_cache_manager()
{
	global $cache_enabled, $valid_nonce;

	$valid_nonce = wp_verify_nonce($_REQUEST['_wpnonce'], 'wp-super-cache');

	if (isset($_POST['wp_cache_status']) && $valid_nonce) {
		switch ( $_POST[ 'wp_cache_status' ] ) {
			case 'all':
				ob_start();
				wp_cache_check_link();
				wp_cache_verify_config_file();
				wp_cache_verify_cache_dir();
				wp_cache_check_global_config();
				wp_cache_enable();
				$_POST['updatehtaccess'] = 1;
				wsc_mod_rewrite();
				ob_end_clean();
				break;
			case 'none':
				ob_start();
				$_REQUEST['wp_delete_cache'] = 1;
				wp_cache_files();
				wp_cache_disable();
				ob_end_clean();
				break;
		}
	}
	?>

	<div class="wrap">
		<h2>Super Cache</h2>
		<form name="wp_manager" action="" method="post">
			<br />
			<label>
				<input type='radio' name='wp_cache_status' value='all'
					<?php if( $cache_enabled == true ) { echo 'checked=checked'; } ?> />
				<strong>On</strong>
			</label>
			<label>
				<input type='radio' name='wp_cache_status' value='none'
					<?php if( $cache_enabled == false ) { echo 'checked=checked'; } ?> />
				<strong>Off</strong>
			</label><br />
			<?php wp_nonce_field('wp-super-cache'); ?>
			<div class='submit'><input type='submit' value='Update Status' /></div>
		</form>
	</div>

	<?php
}

function ld_super_cache_add_pages()
{
	add_options_page('Super Cache', 'Super Cache', 'manage_options', __FILE__, 'ld_super_cache_cache_manager');	
}

add_action('admin_menu', 'ld_super_cache_add_pages');

function ld_super_cache_disable_menus($submenus = array())
{
	$submenus[] = 'wpsupercache';
	return $submenus;
}

add_filter('ld_disable_submenus', 'ld_super_cache_disable_menus');

function ld_super_cache_write_conditions($condition_rules)
{
	$condition_rules[] = "RewriteCond %{HTTP:Cookie} !^.*(ld-auth).*$";
	return $condition_rules;
}

add_filter('supercacherewriteconditions', 'ld_super_cache_write_conditions');
