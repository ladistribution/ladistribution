<?php

function wp_check_password($password, $hash, $user_id = '')
{
	if ($hash == sha1($password)) {
		return true;
	}

	global $wp_hasher;

	// If the stored hash is longer than an MD5, presume the
	// new style phpass portable hash.
	if ( empty($wp_hasher) ) {
		require_once( ABSPATH . 'wp-includes/class-phpass.php');
		// By default, use the portable hash from phpass
		$wp_hasher = new PasswordHash(8, TRUE);
	}

	$check = $wp_hasher->CheckPassword($password, $hash);

	return apply_filters('check_password', $check, $password, $hash, $user_id);
}

function get_userdata( $user_id )
{
	global $wpdb;

	$user_id = absint($user_id);
	if ( $user_id == 0 )
		return false;

	if ( !$wp_user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID = %d LIMIT 1", $user_id)) )
		return false;

	require_once 'Ld/Auth.php';
	$ld_users = Ld_Auth::getUsers();
	foreach ($ld_users as $ld_user) {

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

	throw new Exception("User not found in LD user backend.");
}

function get_userdatabylogin( $user_login )
{
	global $wpdb;

	$user_login = sanitize_user( $user_login );

	if ( empty( $user_login ) )
		return false;
	
	require_once 'Ld/Auth.php';
	$ld_users = Ld_Auth::getUsers();
	foreach ($ld_users as $ld_user) {

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

	throw new Exception("User not found in LD user backend.");
}

function ld_get_user_by_openid( $openid , $id = null )
{
	require_once 'Ld/Auth.php';
	$ld_users = Ld_Auth::getUsers();
	foreach ($ld_users as $ld_user) {
		foreach ($ld_user['identities'] as $identity) {
			if ($identity == $openid) {
				$user = get_userdatabylogin($ld_user['username']);
				return $user->ID;
			}
		}
	}
	return $id;
}
