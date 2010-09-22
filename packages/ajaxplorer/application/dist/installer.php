<?php

class Ld_Installer_Ajaxplorer extends Ld_Installer
{

	public function postInstall($preferences = array())
	{
		if (isset($preferences['administrator'])) {
			$username = $preferences['administrator']['username'];
			$this->setUserRoles(array($username => 'administrator'));
		}

		Ld_Files::createDirIfNotExists( $this->getAbsolutePath() . '/public/' );

		Ld_Http::get( $this->getSite()->getBaseUrl() . $this->getInstance()->getPath() . '/index.php?ignore_tests=true' );
	}

	public function postUpdate()
	{
	}

	public $roles = array('user', 'administrator');

	public $defaultRole = 'user';

	public function getRoles()
	{
		return $this->roles;
	}

	public function getUserRoles()
	{
		defined('AJXP_EXEC') || define('AJXP_EXEC', true);
		require_once( $this->getAbsolutePath() . '/server/classes/class.AJXP_Utils.php' );
		$serialDir = $this->getAbsolutePath() . '/server/users';

		$roles = array();
		foreach ($this->getSite()->getUsers() as $user) {
			$username = $user['username'];
			$file = $serialDir."/".$username."/rights.ser";
			$rights = AJXP_Utils::loadSerialFile($file);
			if (isset($rights["ajxp.admin"]) && $rights["ajxp.admin"] == true) {
				$roles[$username] = 'administrator';
			} else {
				$roles[$username] = 'user';
			}
		}
		return $roles;
	}

	public function setUserRoles($roles)
	{
		defined('AJXP_EXEC') || define('AJXP_EXEC', true);
		require_once( $this->getAbsolutePath() . '/server/classes/class.AJXP_Utils.php' );
		$serialDir = $this->getAbsolutePath() . '/server/users';

		foreach ($roles as $username => $role) {
			$file = $serialDir."/".$username."/rights.ser";
			$rights = AJXP_Utils::loadSerialFile($file);
			$rights["ajxp.admin"] = ($role == 'administrator') ? true : false;
			AJXP_Utils::saveSerialFile($file, $rights, true);
		}
	}

}
