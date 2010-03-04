<?php

require_once 'weave_user/base.php';
require_once 'weave_constants.php';

class WeaveAuthentication implements WeaveAuthenticationBase
{
	var $_username = null;

	function __construct($username)
	{
		$this->_username = $username;
	}

	function open_connection()
	{
		return 1;
	}
	
	function get_connection()
	{
		return null;
	}

	function authenticate_user($password)
	{
		$result = Ld_Auth::authenticate($this->_username, $password);
		if ($result->isValid()) {
			$user = Ld_Auth::getUser();
			if ($user) {
				return $user['id'];
			}
		}
		return null;
	}

	function get_user_alert()
	{
		return "";
	}

}
