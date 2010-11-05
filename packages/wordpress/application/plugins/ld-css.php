<?php
/*
Plugin Name: LD custom css
Plugin URI: http://h6e.net/wordpress/plugins/ld-css
Description: Let the user add custom CSS rules to his blog
Version: 0.5.2
Author: h6e.net
Author URI: http://h6e.net/
*/

/*
 Inspired/derivated from Custom User CSS plugin
 by Jeremiah Orem <jeremy.orem@gmail.com>
 http://wordpress.org/extend/plugins/custom-user-css/
 http://blog.oremj.com/2009/02/11/custom-user-css-wordpress-plugin/
*/

load_plugin_textdomain('ld', null, 'ld');

add_action('admin_menu', 'ld_custom_css_menu', 102);
add_action('wp_head', 'ld_custom_css');

function ld_custom_css()
{
	$ld_custom_css = get_option('ld_custom_css');
	if (!empty($ld_custom_css)) {
		echo '<style type="text/css">' . "\n";	
		echo htmlspecialchars($ld_custom_css) . "\n";
		echo '</style>' . "\n";
	}
}

function ld_custom_css_menu()
{
	add_theme_page(__('Custom CSS', 'ld'), __('Custom CSS', 'ld'), 'edit_theme_options', __FILE__, 'ld_custom_css_edit');
}

function ld_custom_css_edit()
{
	$opt_name = 'ld_custom_css';

	$css_val = get_option( $opt_name );
	if (empty($css_val)) {
		$css_val = "/* " . __('Custom CSS', 'ld') . " */\n\n";
	}

	if (isset($_POST['action']) && $_POST['action'] == 'update' ) {
		$css_val = $_POST[ $opt_name ];
		update_option( $opt_name, $css_val );
		?>
		<div class="updated"><p><strong><?php _e('Options saved.', 'ld' ); ?></strong></p></div>
		<?php
	}
    
	?>

	<div class="wrap">
	<h2><?php _e('Custom CSS', 'ld') ?></h2>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
	<?php wp_nonce_field('update-options'); ?>

	<table class="form-table">

	<tr valign="top">
	<!-- <th scope="row">Custom CSS</th> -->
	<td><textarea cols="70" rows="25" name="<?php echo $opt_name ?>" class="codepress css"><?php echo $css_val ?></textarea>
	</tr>

	</table>

	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="<?php echo $opt_name ?>" />

	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>

	</form>
	</div>

	<?php
}
