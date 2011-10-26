<?php
/*
Plugin Name: LD auth
Plugin URI: http://h6e.net/wordpress/plugins/ld-auth
Description: Handle authentification through La Distribution backend
Version: 0.6.1
Author: h6e.net
Author URI: http://h6e.net/
*/

class Ld_Wordpress_Auth
{

	function site()
	{
		return Zend_Registry::get('site');
	}

	function auto_login()
	{
		if (empty($_COOKIE[LOGGED_IN_COOKIE]) && Ld_Auth::isAuthenticated()) {
			$wp_user = wp_get_current_user();
			wp_set_auth_cookie($wp_user->ID);
		} else if (isset($_COOKIE[LOGGED_IN_COOKIE]) && !Ld_Auth::isAuthenticated()) {
			wp_set_current_user(0);
			wp_logout();
		}
	}

	function authenticate_username_password($user, $username, $password)
	{
		if ( is_a($user, 'WP_User') ) { return $user; }

		if ( !empty($username) && !empty($password) ) {
			$result = Ld_Auth::authenticate($username, $password);
			if ($result->isValid()) {
				$user = get_userdatabylogin($username);
			}
		}

		return $user;
	}

	function authenticate_ld_cookie($user, $username, $password)
	{
		if ( is_a($user, 'WP_User') ) { return $user; }

		if ( empty($username) && empty($password) ) {
			if (Ld_Auth::isAuthenticated()) {
				$user = get_userdatabylogin(Ld_Auth::getUsername());
			}
		}

		return $user;
	}

	function logout()
	{
		Ld_Auth::logout();
	}

	function get_user_by_openid( $openid , $id = null )
	{
		$ld_user = self::site()->getUserByUrl($openid);
		if ($ld_user) {
			$user = get_userdatabylogin($ld_user['username']);
			return $user->ID;
		}
		return $id;
	}

	function get_ld_user($user_login)
	{
		$ld_user = self::site()->getUser($user_login);
		return $ld_user;
	}

	function get_wp_user($user_id)
	{
		global $wpdb;

		$wp_user = new WP_User($user_id);

		if ( !$wp_user )
			return false;

		$user = array(
			'hash' 		=> $wp_user->user_pass,
			'username' 	=> $wp_user->user_login,
			'fullname' 	=> $wp_user->display_name,
			'email'		=> $wp_user->user_email
		);

		return $user;
	}

	function user_register($user_id)
	{
		$user = self::get_wp_user($user_id);
		$user['origin'] = 'Wordpress:register';
		self::site()->addUser($user, false);
	}

	function profile_update($user_id)
	{
		$user = self::get_wp_user($user_id);
		self::site()->updateUser($user['username'], $user);
	}

	function login_url($login_url = '', $redirect = '')
	{
		if (class_exists('Ld_Ui')) {
			$login_url = Ld_Ui::getAdminUrl(array(
				'module' => 'default', 'controller' => 'auth', 'action' => 'login',
				'referer' => empty($redirect) ? null : urlencode($redirect)
			));
		}
		return $login_url;
	}

}

// Hooks

add_action('plugins_loaded', array('Ld_Wordpress_Auth', 'auto_login'), 3);

add_action('wp_logout', array('Ld_Wordpress_Auth', 'logout'));

add_filter('openid_get_user_by_openid', array('Ld_Wordpress_Auth', 'get_user_by_openid'));

add_action('user_register', array('Ld_Wordpress_Auth', 'user_register'));

add_action('profile_update', array('Ld_Wordpress_Auth', 'profile_update'));

add_filter('login_url', array('Ld_Wordpress_Auth', 'login_url'));

add_filter('authenticate', array('Ld_Wordpress_Auth', 'authenticate_username_password'), 15, 3);

add_filter('authenticate', array('Ld_Wordpress_Auth', 'authenticate_ld_cookie'), 25, 3);

// Replacable WordPress functions

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
function get_currentuserinfo() {
	global $current_user;

	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST )
		return false;

	if ( ! empty($current_user) )
		return;

	if (Ld_Auth::isAuthenticated()) {
		$user = get_userdatabylogin(Ld_Auth::getUsername());
		if (isset($user)) {
			wp_set_current_user($user->ID);
			return;
		}
	}

	if ( ! $user = wp_validate_auth_cookie() ) {
		 if ( is_blog_admin() || is_network_admin() || empty($_COOKIE[LOGGED_IN_COOKIE]) || !$user = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in') ) {
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

	$wp_user = new WP_User($user_id);

	if ( !$wp_user )
		return false;

	$ld_user = Ld_Wordpress_Auth::get_ld_user($wp_user->user_login);

	if ($ld_user) {
		$wp_user->user_pass  		= $ld_user['hash'];
		$wp_user->user_nicename		= $ld_user['username'];
		$wp_user->nickname			= $ld_user['username'];
		$wp_user->user_email		= $ld_user['email'];
		$wp_user->display_name		= !empty($ld_user['fullname']) ? $ld_user['fullname'] : $ld_user['username'];
	}

	return $wp_user;
}
endif;

if ( !function_exists('get_userdatabylogin') ) :
function get_userdatabylogin( $user_login )
{
	global $wpdb;

	$user_login = sanitize_user( $user_login );

	if ( empty( $user_login ) )
		return false;

	$ld_user = Ld_Wordpress_Auth::get_ld_user($user_login);

	if ($ld_user) {

		// if user doesn't exists in DB, we insert it
		$query = $wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_login = %s LIMIT 1", $ld_user['username']);

		if ( !$wp_user = $wpdb->get_row($query) ) {
			$data = array(
				'user_login' => $ld_user['username'],
				'user_email' => $ld_user['email'],
				'user_pass'  => $ld_user['hash']
			);
			$wpdb->insert( $wpdb->users, $data);
			$user_id = (int) $wpdb->insert_id;

			$wp_user = new WP_User($user_id);
			$wp_user->set_role(get_option('default_role'));

			update_user_meta( $user_id, 'rich_editing', 'true');

			return get_userdata($user_id);
		}

		return get_userdata($wp_user->ID);
	}

	// search in WP DB ...

	$query = $wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_login = %s LIMIT 1", $user_login);
	if ( $wp_user = $wpdb->get_row($query) ) {
		return get_userdata($wp_user->ID);
	}

	return false;
}
endif;

if ( !function_exists('wp_set_password') ) :
function wp_set_password( $password, $user_id ) {
	global $wpdb;
	$hash = wp_hash_password($password);
	$wpdb->update($wpdb->users, array('user_pass' => $hash, 'user_activation_key' => ''), array('ID' => $user_id) );
	wp_cache_delete($user_id, 'users');
	Ld_Wordpress_Auth::profile_update($user_id);
}
endif;
