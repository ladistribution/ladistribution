<?php
/*
Plugin Name: LD ui
Plugin URI: http://h6e.net/wordpress/plugins/ld-ui
Description: Enable some La Distribution UI elements
Version: 0.2a
Author: h6e
Author URI: http://h6e.net/
*/

function ld_admin_head()
{
	echo '<link rel="stylesheet" type="text/css" href="' . LD_BASE_URL . '/css/ld-ui/ld-bars.css' .'" />'."\n";
	echo '<style type="text/css"> #footer { display:none }</style>'."\n";
}

add_action('admin_head', 'ld_admin_head');

function ld_template_head()
{
	?>
	<script type="text/javascript" src="<?php echo LD_JS_URL ?>jquery/jquery.js"></script>
	<script type="text/javascript" src="<?php echo LD_JS_URL ?>ld/ld.js"></script>
	<?php
	echo '<link rel="stylesheet" type="text/css" href="' . LD_BASE_URL . '/css/ld-ui/ld-bars.css' .'" />'."\n";
	echo '<link rel="stylesheet" type="text/css" href="' . LD_BASE_URL . '/css/ld-ui/ld-dialog.css' .'" />'."\n";
}

add_action('wp_head', 'ld_template_head');

add_action('login_head', 'ld_template_head');

function ld_footer()
{
	require_once 'Ld/Ui.php';
	Ld_Ui::super_bar();
}

add_action('wp_footer', 'ld_footer');

add_action('admin_footer', 'ld_footer');

add_action('login_form', 'ld_footer');

function ld_top_bar()
{
	global $current_user;
    ?>
    <div class="h6e-top-bar"><div class="h6e-top-bar-inner">
      <div class="a">
          <?php bloginfo('name') ?>
      </div>
      <div class="b">
         <?php if ( is_user_logged_in() ) : ?>

             <?php printf(
             __('Howdy, <a href="%1$s" title="Edit your profile">%2$s</a> (%3$s)'),
             admin_url('profile.php'), $current_user->display_name, $current_user->user_login
             ) ?>
             <a href="<?php echo admin_url() ?>" title="<?php _e('Admin') ?>"><?php _e('Admin'); ?></a>
             <a href="<?php echo wp_logout_url() ?>" title="<?php _e('Log Out') ?>"><?php _e('Log Out'); ?></a>

         <?php else : ?>

             <?php wp_loginout() ?>

         <?php endif; ?>
      </div>
    </div></div>
    
    <script type="text/javascript">
    (function($) {
        $(document).ready(function($){
            $(".h6e-top-bar a[href$='wp-login.php']").click(function() {
                return Ld.handleLogin({
                    'href': $(this).attr('href'),
                    'action': $(this).attr('href'),
                    'baseUrl': '<?php echo LD_ADMIN_URL ?>',
                    'wrapper': "#wrapper"
                });
            });
        });
    })(jQuery);
    </script>

    <?php

}
