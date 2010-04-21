<?php
/*
Plugin Name: Ld Auth
Plugin URI: http://h6e.net/bbpress/plugins/ld-auth
Description: Handle authentification through La Distribution backend
Version: 0.4.1
Author: h6e.net
Author URI: http://h6e.net/
*/

class Ld_Bbpress_Auth
{

	function get_bb_user($user_id)
	{
		global $bbdb;

		if ( !$bb_user = $bbdb->get_row($bbdb->prepare("SELECT * FROM $bbdb->users WHERE ID = %d LIMIT 1", $user_id)) )
			return false;

		$ld_user = array(
			'username' 	=> $bb_user->user_nicename,
			'hash' 		=> $bb_user->user_pass,
			'fullname' 	=> $bb_user->display_name,
			'email'		=> $bb_user->user_email
		);

		return $ld_user;
	}

	function get_bb_user_by_login($login)
	{
		global $bbdb, $wp_users_object;
		if (isset($wp_users_object)) {
			// test if the users exists in bbPress DB
			$bb_user = $wp_users_object->get_user($login, array( 'by' => 'login' ) );
			// if not create the user in bbPress DB
			if (empty($bb_user)) {
				$ld_user = Zend_Registry::get('site')->getUser($login);
				if ($ld_user) {
					$data = array( 'user_login' => $ld_user['username'], 'user_email' => $ld_user['email'] );
					$bb_user = $wp_users_object->new_user( $data );
					if (!is_wp_error($bb_user)) {
						bb_update_usermeta( $bb_user['ID'], $bbdb->prefix . 'capabilities', array('member' => true) );
					}

				}
			}
			return $bb_user;
		}
		return null;
	}

	function logout()
	{
		Ld_Auth::logout();
	}

	function user_register($user_id)
	{
		$ld_user = self::get_bb_user($user_id);
		$ld_user['origin'] = 'Bbpress:register';
		Zend_Registry::get('site')->addUser($ld_user);
	}

	function profile_update($user_id)
	{
		$ld_user = self::get_bb_user($user_id);
		Zend_Registry::get('site')->updateUser($ld_user['username'], $ld_user);
	}

}

// Hooks

add_action('bb_user_logout', array('Ld_Bbpress_Auth', 'logout'));
	
add_action('register_user', array('Ld_Bbpress_Auth', 'user_register'));

add_action('profile_edited', array('Ld_Bbpress_Auth', 'profile_update'));

add_action('bb_update_user_password', array('Ld_Bbpress_Auth', 'profile_update'));

// Replacable bbPress functions

if ( !function_exists('bb_get_current_user') ) :
function bb_get_current_user() {
	global $current_user;

	// LD authentication
	if (Ld_Auth::isAuthenticated()) {
		$bb_user = Ld_Bbpress_Auth::get_bb_user_by_login( Ld_Auth::getUsername() );
		// set the current user
		if (isset($bb_user)) {
			if (empty($current_user)) {
				$current_user = $bb_user;
			}
			bb_set_current_user($bb_user->ID);
			return $bb_user;
		}
	}

	// bbPress authentication
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

if ( !function_exists('bb_login') ) :
function bb_login( $login, $password, $remember = false ) {
	// LD login
    $result = Ld_Auth::authenticate($login, $password);
	if ($result->isValid()) {
		return bb_get_current_user();
	}

	// bbPress login
	$user = bb_check_login( $login, $password );
	if ( $user && !is_wp_error( $user ) ) {
		bb_set_auth_cookie( $user->ID, $remember );
		do_action('bb_user_login', (int) $user->ID );
	}

	return $user;
}
endif;
