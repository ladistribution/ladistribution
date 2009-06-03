<?php

class Installer_Indexhibit extends Ld_Installer
{

	public function postInstall($preferences = array())
	{
		$this->preferences = $preferences;

		$this->adminAbsolutePath = $this->absolutePath . '/ndxz-studio';

		Ld_Files::createDirIfNotExists("$this->absolutePath/files/gimgs");

		Ld_Files::createDirIfNotExists("$this->adminAbsolutePath/config");

		$this->_performWebInstall();

		$this->_createConfigFile();

		$this->_updateDatabase();

		Ld_Files::unlink($this->adminAbsolutePath . '/install.php');
	}

	private function _createConfigFile()
	{
		$cfg  = "<?php\n";
		$cfg .= "require_once dirname(__FILE__) . '/../../dist/prepend.php';\n";
		$cfg .= '$indx["db"]   = $db["name"];' . "\n";
		$cfg .= '$indx["user"] = $db["user"];' . "\n";
		$cfg .= '$indx["pass"] = $db["password"];' . "\n";
		$cfg .= '$indx["host"] = $db["host"];' . "\n";
		$cfg .= '$indx["sql"]  = "mysql";' . "\n";
		Ld_Files::put($this->adminAbsolutePath . '/config/config.php', $cfg);
	}
    
	private function _performWebInstall()
	{
		$adminUrl = $this->instance->getUrl() . 'ndxz-studio';

		// Set Lang
		$this->httpClient = new Zend_Http_Client();
		$this->httpClient->setCookieJar();
		$this->httpClient->setUri("$adminUrl/install.php");
		$this->httpClient->setParameterPost(array(
			'submitLang' => 'submit',
			'user_lang'  => $this->preferences['lang']
		));
		$response = $this->httpClient->request('POST');

		if (constant('LD_DEBUG')) {
			echo $response->getBody();
		}

		$databases = $this->site->getDatabases();
		$db = $databases[ $this->instance->getDb() ];

		// Perform SQL Install
		$this->httpClient->setUri("$adminUrl/install.php?page=2");
		$this->httpClient->setParameterPost(array(
			'n_submit' => 'submit',
			'n_host' => $db['host'],
			'n_name' => $db['name'],
			'n_user' => $db['user'],
			'n_pwd'  => $db['password'],
			'n_site' => $this->preferences['title']
		));
		$response = $this->httpClient->request('POST');

		if (constant('LD_DEBUG')) {
			echo $response->getBody();
		}
	}

	private function _updateDatabase()
	{
		global $indx;

		defined('SITE') OR define('SITE', 'Bonjour!');

		require_once $this->adminAbsolutePath . '/config/config.php';
		require_once $this->adminAbsolutePath . '/db/db.mysql.php';

		$db = new Db();

		$users_table = $this->dbPrefix . 'users';
		$objects_prefs_table = $this->dbPrefix . 'objects_prefs';

		$username = $db->escape_str($this->preferences['admin_username']);
		$password = md5($this->preferences['admin_password']);

		$title = $db->escape_str($this->preferences['title']);

		// We now update default password, replacing by user defined one
		$db->query("UPDATE $users_table SET userid = '$username', PASSWORD = '$password' WHERE userid = 'index1'");

		// We update Exhibit title
		$db->query("UPDATE $objects_prefs_table SET obj_name = '$title' WHERE obj_id = 1");	
	}

}
