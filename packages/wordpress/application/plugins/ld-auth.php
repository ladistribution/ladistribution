<?php
/*
Plugin Name: LD auth
Plugin URI: http://h6e.net/wordpress/plugins/ld-auth
Description: Handle authentification through La Distribution backend
Version: 0.2a
Author: h6e
Author URI: http://h6e.net/
*/

function ld_get_auth()
{
	if (Zend_Registry::isRegistered('authStorage')) {
		$authStorage = Zend_Registry::get('authStorage');
	} else {
		$authStorage = new Zend_Auth_Storage_Session( /* namespace */ null );
	}

	$auth = Zend_Auth::getInstance();
	$auth->setStorage($authStorage);
	
	return $auth;
}

function ld_autologin_user()
{
	if (!is_user_logged_in()) {
		$auth = ld_get_auth();
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
	$auth = ld_get_auth();
	if ($auth->hasIdentity()) {
		$auth->clearIdentity();
	}
}

add_action('wp_logout', 'ld_logout');

if ( !function_exists('auth_redirect') ) :
function auth_redirect()
{
	$user = wp_get_current_user();
	if (isset($user)) {
		return;
	}
	$login_url = site_url('wp-login.php', 'login');
	wp_redirect($login_url);
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

	$site = Zend_Registry::get('site');
	foreach ($site->getUsers() as $ld_user) {

		if ($ld_user['username'] == $wp_user->user_login) {

			$wp_user->user_pass  		= $ld_user['hash'];
			$wp_user->user_nicename		= $ld_user['fullname'];
			$wp_user->display_name		= $ld_user['fullname'];
			$wp_user->nickname			= $ld_user['username'];
			$wp_user->user_email		= $ld_user['email'];

			_fill_user($wp_user);

			return $wp_user;
		}

	}

	wp_die( __('User not found in LD user backend.') );
}
endif;

if ( !function_exists('get_userdatabylogin') ) :
function get_userdatabylogin( $user_login )
{
	global $wpdb;

	$user_login = sanitize_user( $user_login );

	if ( empty( $user_login ) )
		return false;

    $site = Zend_Registry::get('site');
	foreach ($site->getUsers() as $ld_user) {

		if ($ld_user['username'] == $user_login) {

			// if user doesn't exists in DB, we insert it
			$query = $wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_login = %s LIMIT 1", $ld_user['username']);

			if ( !$wp_user = $wpdb->get_row($query) ) {
				$data = array('user_login' => $ld_user['username']);
				$wpdb->insert( $wpdb->users, $data);
				$user_id = (int) $wpdb->insert_id;

				return get_userdata($user_id);
			}

			return get_userdata($wp_user->ID);
		}
	}
    
	wp_die( __('User not found in LD user backend.') );
}
endif;

function ld_get_user_by_openid( $openid , $id = null )
{
	$site = Zend_Registry::get('site');

	foreach ($site->getUsers() as $ld_user) {
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
