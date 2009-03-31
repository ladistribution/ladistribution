<?php

class Installer_Indexhibit extends Ld_Installer
{

	function install($preferences = array())
	{
		parent::install($preferences);

		$this->preferences = $preferences;

		$this->adminAbsolutePath = $this->absolutePath . '/ndxz-studio';

		if (!file_exists("$this->absolutePath/files") || !file_exists("$this->absolutePath/files/gimgs")) {
			mkdir("$this->absolutePath/files/gimgs", 0777, true);
		}

		if (!file_exists($this->adminAbsolutePath . '/config')) {
			mkdir($this->adminAbsolutePath . '/config', 0777, true);
		}

		$this->_createPrependFile();
		require_once $this->absolutePath . '/dist/prepend.php';

		$this->_performWebInstall();

		$this->_createConfigFile();

		$this->_updateDatabase();

		// We don't need the web installer anymore
		$this->_unlink($this->adminAbsolutePath . '/install.php');
	}

	private function _createPrependFile()
	{
		$prepend  = "<?php\n";
		$prepend .= "require_once dirname(__FILE__) . '/config.php';\n";
		$prepend .= "define('PX', '$this->dbPrefix');\n";
		file_put_contents($this->absolutePath . '/dist/prepend.php', $prepend);
	}

	private function _createConfigFile()
	{
		$cfg  = "<?php\n";
		$cfg .= "require_once dirname(__FILE__) . '/../../dist/prepend.php';\n";
		$cfg .= '$indx["db"]   = LD_DB_NAME;' . "\n";
		$cfg .= '$indx["user"] = LD_DB_USER;' . "\n";
		$cfg .= '$indx["pass"] = LD_DB_PASSWORD;' . "\n";
		$cfg .= '$indx["host"] = LD_DB_HOST;' . "\n";
		$cfg .= '$indx["sql"]  = "mysql";' . "\n";
		file_put_contents($this->adminAbsolutePath . '/config/config.php', $cfg);
	}
    
	private function _performWebInstall()
	{
		$adminUrl = LD_BASE_URL . $this->preferences['path'] . '/ndxz-studio';
        
		// Set Lang
		$this->httpClient = new Zend_Http_Client();
		$this->httpClient->setCookieJar();
		$this->httpClient->setUri("$adminUrl/install.php");
		$this->httpClient->setParameterPost(array(
			'submitLang' => 'submit',
			'user_lang'  => $this->preferences['lang']
		));
		$response = $this->httpClient->request('POST');

		if (defined('DEBUG') && DEBUG === true) {
			echo $response->getBody();
		}

		// Perform SQL Install
		$this->httpClient->setUri("$adminUrl/install.php?page=2");
		$this->httpClient->setParameterPost(array(
			'n_submit' => 'submit',
			'n_host' => LD_DB_HOST,
			'n_name' => LD_DB_NAME,
			'n_user' => LD_DB_USER,
			'n_pwd'  => LD_DB_PASSWORD,
			'n_site' => $this->preferences['title']
		));
		$response = $this->httpClient->request('POST');

		if (defined('DEBUG') && DEBUG === true) {
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
		$db->query("UPDATE $objects_prefs SET obj_name = '$title' WHERE obj_id = 1");	
	}

}
