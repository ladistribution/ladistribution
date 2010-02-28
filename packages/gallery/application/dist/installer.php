<?php

class Ld_Installer_Gallery extends Ld_Installer
{

	public function postInstall($preferences = array())
	{
		defined("DOCROOT") or define("DOCROOT", $this->getAbsolutePath() . "/");
		defined("VARPATH") or define("VARPATH", DOCROOT . "var/");
		defined("SYSPATH") or define("SYSPATH", "DEFINED_TO_SOMETHING_SO_THAT_WE_CAN_KEEP_CONSISTENT_PREAMBLES_IN_THE_INSTALLER");

		require_once($this->getAbsolutePath() . '/installer/installer.php');

		$databases = $this->getSite()->getDatabases();
		$db = $databases[ $this->getInstance()->getDb() ];
		$dbPrefix = $this->getInstance()->getDbPrefix();

		$config = array(
			"host" 		=> $db['host'],
			"user"		=> $db['user'],
			"password"	=> $db['password'],
			"dbname"	=> $db['name'],
			"prefix"	=> $dbPrefix,
			"type"		=> "mysqli"
		);

		installer::connect($config);
		installer::select_db($config);
		installer::db_empty($config);
		installer::unpack_var();
		installer::unpack_sql($config);

		// if (isset($preferences['administrator'])) {
		// 	$preferences['admin_username'] = $preferences['administrator']['username'];
		// 	$preferences['admin_fullname'] = $preferences['administrator']['fullname'];
		// 	$preferences['admin_email'] = $preferences['administrator']['email'];
		// }

		if (isset($preferences['administrator'])) {
			$username = $preferences['administrator']['username'];
			$this->setUserRoles(array($username => 'administrator'));
		}

		// try {
		// 	$user = user::lookup_by_name($preferences['admin_username']);
		// } catch (Exception $e) {
		// 	$user = null;
		// }

		$con = $this->instance->getDbConnection();

		// if (empty($user)) {
		// 	$name = $preferences['admin_username'];
		// 	$full_name = $preferences['admin_fullname'];
		// 	$email = $preferences['admin_email'];
		// 	$sql = installer::prepend_prefix($config["prefix"],
		// 		"INSERT INTO {users} SET `admin` = 1,  `name` = '$name', `full_name` = '$full_name', `email` = '$email'");
		// 	$result = $con->query($sql);
		// 	if (!$result) {
		// 		throw Exception(mysql_error());
		// 	}
		// }

		installer::create_private_key($config);

		// $salt = $this->_getSalt();
		// $hashed_password = $salt . md5($salt . $preferences['admin_password']);
		// $sql = installer::prepend_prefix($config["prefix"],
		// 	"UPDATE {users} SET `password` = '$hashed_password' WHERE `id` = 2");
		// $result = $con->query($sql);
		// if (!$result) {
		// 	throw Exception(mysql_error());
		// }

		$sql = installer::prepend_prefix($config["prefix"],
			"DELETE FROM {users} WHERE `id` = 2");
		$result = $con->query($sql);
		if (!$result) {
			throw Exception(mysql_error());
		}

		$sql = installer::prepend_prefix($config["prefix"],
			"INSERT INTO {modules} SET `active` = 1,  `name` = 'ld', `version` = '1'");
		$result = $con->query($sql);
		if (!$result) {
			throw Exception(mysql_error());
		}
	}

	// protected function _getSalt()
	// {
	// 	$salt = "";
	// 	for ($i = 0; $i < 4; $i++) {
	// 		$char = mt_rand(48, 109);
	// 		$char += ($char > 90) ? 13 : ($char > 57) ? 7 : 0;
	// 		$salt .= chr($char);
	// 	}
	// 	return $salt;
	// }

	public function postUpdate()
	{
		// upgrade code there
		// manual upgrade = http://example.com/gallery3/index.php/upgrader
	}

	public $roles = array('administrator', 'user');

	public $defaultRole = 'user';

	public function getRoles()
	{
		return $this->roles;
	}

}
