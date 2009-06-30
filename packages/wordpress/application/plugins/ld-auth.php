<?php
/*
Plugin Name: LD auth
Plugin URI: http://h6e.net/wordpress/plugins/ld-auth
Description: Handle authentification through La Distribution backend
Version: 0.2-27-1
Author: h6e
Author URI: http://h6e.net/
*/

function ld_autologin_user()
{
	if (!is_user_logged_in()) {
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$user = get_userdatabylogin($auth->getIdentity());
			if (isset($user)) {
				wp_set_current_user($user->ID, $user->user_login);
			}
		}
	}
}

add_action('init', 'ld_autologin_user');

function ld_logout()
{
	$auth = Zend_Auth::getInstance();
	if ($auth->hasIdentity()) {
		$auth->clearIdentity();
	}
}

add_action('wp_logout', 'ld_logout');

class Ld_Auth_Adapter_File_Wordpress implements Zend_Auth_Adapter_Interface
{
	public function authenticate()
	{
		if (is_user_logged_in()) {
			$user = wp_get_current_user();
			return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user->user_login);
		}
		return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
	}
}

function ld_handle_current_user()
{
	$auth = Zend_Auth::getInstance();
	if (is_user_logged_in() && !$auth->hasIdentity()) {
		$adapter = new Ld_Auth_Adapter_File_Wordpress();
		$auth->authenticate($adapter);
	}	
}

add_action('set_current_user', 'ld_handle_current_user');

if ( !function_exists('auth_redirect') ) :
function auth_redirect()
{
	if (is_user_logged_in()) {
		return;
	}
	$login_url = site_url('wp-login.php', 'login');
	wp_redirect($login_url);
	exit();
}
endif;

if ( !function_exists('get_currentuserinfo') ) :
function get_currentuserinfo($cookie = '', $scheme = '') {
	global $current_user;

	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST )
		return false;

	if ( ! empty($current_user) )
		return;

	$auth = Zend_Auth::getInstance();
	if ($auth->hasIdentity()) {
		$user = get_userdatabylogin($auth->getIdentity());
		if (isset($user)) {
			wp_set_current_user($user->ID);
		}
	}

	if ( ! $user = wp_validate_auth_cookie() ) {
		 if ( empty($_COOKIE[LOGGED_IN_COOKIE]) || !$user = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in') ) {
		 	wp_set_current_user(0);
		 	return false;
		 }
	}

	wp_set_current_user($user);
}
endif;

if ( !function_exists('get_userdata') ) :
function get_userdata( $user_id )
{
	global $wpdb;

	$user_id = absint($user_id);
	if ( $user_id == 0 )
		return false;

	if ( !$wp_user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID = %d LIMIT 1", $user_id)) )
		return false;
	
	$ld_user = Zend_Registry::get('site')->getUser($wp_user->user_login);
	if ($ld_user) {
		$wp_user->user_pass  		= $ld_user['hash'];
		$wp_user->user_nicename		= $ld_user['username'];
		$wp_user->display_name		= $ld_user['fullname'];
		$wp_user->nickname			= $ld_user['username'];
		$wp_user->user_email		= $ld_user['email'];
	}

	_fill_user($wp_user);
	return $wp_user;

	// wp_die( __('get_userdata: User not found in LD user backend.') );
}
endif;

if ( !function_exists('get_userdatabylogin') ) :
function get_userdatabylogin( $user_login )
{
	global $wpdb;

	$user_login = sanitize_user( $user_login );

	if ( empty( $user_login ) )
		return false;
	
	$ld_user = Zend_Registry::get('site')->getUser($user_login);

	if ($ld_user) {

		// if user doesn't exists in DB, we insert it
		$query = $wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_login = %s LIMIT 1", $ld_user['username']);

		if ( !$wp_user = $wpdb->get_row($query) ) {
			$data = array('user_login' => $ld_user['username']);
			$wpdb->insert( $wpdb->users, $data);
			$user_id = (int) $wpdb->insert_id;

			$wp_user = new WP_User($user_id);
			$wp_user->set_role(get_option('default_role'));

			return get_userdata($user_id);
		}

		return get_userdata($wp_user->ID);
	}

	return false;
	// wp_die( __('get_userdatabylogin: User not found in LD user backend.') );
}
endif;

function ld_get_user_by_openid( $openid , $id = null )
{
	foreach (Zend_Registry::get('site')->getUsers() as $ld_user) {
		foreach ($ld_user['identities'] as $identity) {
			if ($identity == $openid) {
				$user = get_userdatabylogin($ld_user['username']);
				return $user->ID;
			}
		}
	}
	return $id;
}

add_filter('openid_get_user_by_openid', 'ld_get_user_by_openid');

function ld_get_wp_user($user_id)
{
	global $wpdb;

	if ( !$wp_user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID = %d LIMIT 1", $user_id)) )
		return false;

	$user = array(
		'hash' 		=> $wp_user->user_pass,
		'username' 	=> $wp_user->user_nicename,
		'fullname' 	=> $wp_user->display_name,
		'email'		=> $wp_user->user_email
	);
	
	return $user;
}

function ld_user_register($user_id)
{
	$user = ld_get_wp_user($user_id);
	Zend_Registry::get('site')->addUser($user);
}

add_action('user_register', 'ld_user_register');

function ld_profile_update($user_id)
{
	$user = ld_get_wp_user($user_id);
	Zend_Registry::get('site')->updateUser($user['username'], $user);
}

add_action('profile_update', 'ld_profile_update');

if ( !function_exists('wp_set_password') ) :
function wp_set_password( $password, $user_id ) {
	global $wpdb;
	$hash = wp_hash_password($password);
	$wpdb->update($wpdb->users, array('user_pass' => $hash, 'user_activation_key' => ''), array('ID' => $user_id) );
	wp_cache_delete($user_id, 'users');
	ld_profile_update($user_id);
}
endif;
