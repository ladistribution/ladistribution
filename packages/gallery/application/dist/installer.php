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

		var_dump($config);

		installer::connect($config);
		installer::select_db($config);
		installer::db_empty($config);
		installer::unpack_var();
		installer::unpack_sql($config);
		installer::create_admin($config);

		$username = $preferences['admin_username'];
		$salt = $this->_getSalt();
		$hashed_password = $salt . md5($salt . $preferences['admin_password']);
		$sql = installer::prepend_prefix($config["prefix"],
			"UPDATE {users} SET `password` = '$hashed_password' WHERE `id` = 2");
		mysql_query($sql);
		// $this->getInstance()->getDbConnection()->query($sql);
	
		installer::create_private_key($config);

	}

	protected function _getSalt()
	{
		$salt = "";
		for ($i = 0; $i < 4; $i++) {
			$char = mt_rand(48, 109);
			$char += ($char > 90) ? 13 : ($char > 57) ? 7 : 0;
			$salt .= chr($char);
		}
		return $salt;
	}

}
