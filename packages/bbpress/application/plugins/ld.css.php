<?php
/*
Plugin Name: LD custom css
Plugin URI: http://h6e.net/bbpress/plugins/ld-css
Description: Let the user add custom CSS rules to his blog
Version: 0.2-29-1
Author: h6e.net
Author URI: http://h6e.net/
*/

/*
 Inspired/derivated from Custom User CSS plugin
 by Jeremiah Orem <jeremy.orem@gmail.com>
 http://wordpress.org/extend/plugins/custom-user-css/
 http://blog.oremj.com/2009/02/11/custom-user-css-wordpress-plugin/
*/

add_action('bb_admin_menu_generator', 'ld_custom_css_menu');
add_action('bb_head', 'ld_custom_css');

function ld_custom_css()
{
	echo '<style type="text/css">';	
	echo htmlspecialchars(bb_get_option('ld_custom_css'));
	echo '</style>';
}

function ld_custom_css_menu()
{
	bb_admin_add_submenu( __( 'Custom CSS', 'ld' ), 'manage_themes', 'ld_custom_css_edit', 'themes.php' );
}

function ld_custom_css_edit()
{
	$opt_name = 'ld_custom_css';

	$css_val = bb_get_option( $opt_name );
	if (empty($css_val)) {
		$css_val = "/* Custom CSS */\n\n";
	}

	if( $_POST['action'] == 'update' ) {
		$css_val = $_POST[ $opt_name ];
		bb_update_option( $opt_name, $css_val );
		?>
		<div class="updated"><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div>
		<?php
	}
    
	?>

	<div class="wrap">
	<h2>Custom User CSS</h2>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
	<?php bb_nonce_field('update-options'); ?>

	<textarea cols="70" rows="25" name="<?php echo $opt_name ?>" class="codepress css"><?php echo $css_val ?></textarea>

	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="<?php echo $opt_name ?>" />

	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>

	</form>
	</div>

	<?php
}
