<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class LdAuthenticationPlugin extends AuthenticationPlugin
{

	public $authoritative = true;

	public $autoregistration = true;

	public $password_changeable = false;

	public $provider_name = 'ld';

	function checkPassword($username, $password)
	{
		$result = Ld_Auth::authenticate($username, $password);
		if ($result->isValid()) {
		    return true;
		}
		return false;
    }

	function autoRegister($username, $nickname = null)
	{
		if (is_null($nickname)) {
			$nickname = $username;
		}
		$user = Zend_Registry::get('site')->getUser($username);
		$registration_data = array();
		$registration_data['nickname'] = $nickname;
		if (isset($user['fullname'])) {
			$registration_data['fullname'] = $user['fullname'];
		}
		if (isset($user['email'])) {
			$registration_data['email'] = $user['email'];
		}
		return User::register($registration_data);
	}

	function onInitializePlugin()
	{
		parent::onInitializePlugin();
		if (Ld_Auth::isAuthenticated()) {
			if ( common_current_user() && common_is_real_login() ) {
				return;
			}
			$nickname = Ld_Auth::getUsername();
			$user = new User_username();
			$user->username = $nickname;
			$user->provider_name = $this->provider_name;
			if (!$user->find()) {
				$user = $this->autoRegister($user->username);
				if ($user) {
					User_username::register($user, $nickname, $this->provider_name);
				}
			}
			common_set_user($nickname);
			common_real_login(true);
		} else {
			if ( common_current_user() ) {
				common_set_user(null);
				common_real_login(false); // not logged in
				common_forgetme(); // don't log back in!
			}
		}
	}

	function onEndLogout()
	{
		Ld_Auth::logout();
	}

}
