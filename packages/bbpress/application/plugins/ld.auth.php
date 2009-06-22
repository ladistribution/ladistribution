<?php
/*
Plugin Name: Ld Auth
Plugin URI: http://h6e.net/bbpress/plugins/ld-auth
Description: Handle authentification through La Distribution backend
Version: 0.2-26-1
Author: h6e
Author URI: http://h6e.net/
*/

if ( !function_exists('bb_get_current_user') ) :
function bb_get_current_user() {
	global $wp_users_object, $current_user;
	$auth = Zend_Auth::getInstance();
	if ($auth->hasIdentity()) {
		if (isset($wp_users_object)) {
			$user = $wp_users_object->get_user( $auth->getIdentity(), array( 'by' => 'login' ) );
		}
		if (isset($user)) {
			if (empty($current_user)) {
				$current_user = $user;
			}
			bb_set_current_user($user->ID);
			return $user;
		}
		// create user ...
	}

	global $wp_auth_object;
	return $wp_auth_object->get_current_user();
}
endif;

if ( !function_exists( 'bb_auth' ) ) :
function bb_auth( $scheme = 'auth' ) {
	if ($user = bb_get_current_user()) {
		return;
	}
	if ( !bb_validate_auth_cookie( '', $scheme ) ) {
		nocache_headers();
		if ( 'auth' === $scheme && !bb_is_user_logged_in() ) {
			wp_redirect( bb_get_uri( 'bb-login.php', array( 're' => $_SERVER['REQUEST_URI'] ), BB_URI_CONTEXT_HEADER + BB_URI_CONTEXT_BB_USER_FORMS ) );
		} else {
			wp_redirect( bb_get_uri( null, null, BB_URI_CONTEXT_HEADER ) );
		}
		exit;
	}
}
endif;

function ld_logout()
{
	$auth = Zend_Auth::getInstance();
	if ($auth->hasIdentity()) {
		$auth->clearIdentity();
	}
}

add_action('bb_user_logout', 'ld_logout');
