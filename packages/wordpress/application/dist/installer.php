<?php

class Ld_Installer_Wordpress extends Ld_Installer
{

	public function install($preferences = array())
	{
		parent::install($preferences);

		$this->_createConfigFile();
	}

	private function _createConfigFile()
	{
		$cfg = "<?php\n";

		$cfg .= "defined('ABSPATH') OR define( 'ABSPATH', dirname(__FILE__) . '/' );\n";

		$cfg .= "require_once(ABSPATH . 'dist/prepend.php');\n";

		$cfg .= "define('DB_CHARSET', 'utf8');\n";
		$cfg .= "define('DB_COLLATE', '');\n";

		$cfg .= "define('AUTH_KEY', '" . Ld_Auth::generatePhrase() . "');\n";
		$cfg .= "define('SECURE_AUTH_KEY', '" . Ld_Auth::generatePhrase() . "');\n";
		$cfg .= "define('LOGGED_IN_KEY', '" . Ld_Auth::generatePhrase() . "');\n";
		$cfg .= "define('NONCE_KEY', '" . Ld_Auth::generatePhrase() . "');\n";

		$cfg .= "require_once(ABSPATH . 'wp-settings.php');\n";

		Ld_Files::put($this->getAbsolutePath() . "/wp-config.php", $cfg);
	}

	public function postInstall($preferences = array())
	{
		parent::postInstall($preferences);

		if (isset($preferences['lang'])) {
			$this->getInstance()->setInfos(array('locale' => $preferences['lang']))->save();
		}

		$params = array(
			'title'			=> $preferences['title'],
			'url'			=> $this->getSite()->getBaseUrl() . $preferences['path'],
			'rewrite'		=> defined('LD_REWRITE') && constant('LD_REWRITE') ? 1 : 0,
			'theme'			=> isset($preferences['theme']) ? $preferences['theme'] : null
		);
		
		if (isset($preferences['administrator'])) {
			$params['user_name'] = $preferences['administrator']['username'];
			$params['user_email'] = $preferences['administrator']['email'];
			$params['user_password'] = '';
			$params['user_hash'] = $preferences['administrator']['hash'];
		}

		$this->serviceRequest('init', $params);
    }

	public function postUpdate()
	{
		$this->httpClient = new Zend_Http_Client();
		$this->httpClient->setCookieJar();
		$this->httpClient->setUri($this->getSite()->getBaseUrl() . $this->getInstance()->getPath() . '/wp-admin/upgrade.php?step=1');
		$response = $this->httpClient->request('GET');
	}

	public function postMove()
	{
		$this->serviceRequest('updateUrl');
	}

	// Backup / Restore

	public function getBackupDirectories()
	{
		parent::getBackupDirectories();
		$this->_backupDirectories['uploads'] = $this->getAbsolutePath() . '/wp-content/uploads/';
		return $this->_backupDirectories;
	}

	public function restore($restoreFolder)
	{
		parent::restore($restoreFolder);

		$this->_fixDatabasePrefix();

		if (file_exists($this->getRestoreFolder() . '/uploads')) {
			Ld_Files::copy($this->getRestoreFolder() . '/uploads', $this->getAbsolutePath() . '/wp-content/uploads');
		}

		$this->serviceRequest('updateUrl');

		Ld_Files::unlink($this->getRestoreFolder());
	}

	private function _fixDatabasePrefix()
	{
		$infos = Ld_Files::getJson($this->getRestoreFolder() . '/dist/instance.json');
		$oldDbPrefix = $infos['db_prefix'];

		$dbPrefix = $this->getInstance()->getDbPrefix();
		$dbConnection = $this->getInstance()->getDbConnection('php');

		foreach (array('usermeta' => 'meta_key', 'options' => 'option_name') as $table => $key) {
			$tablename = $dbPrefix . $table;
			$result = $dbConnection->query("SELECT $key FROM $tablename WHERE $key LIKE '$oldDbPrefix%'");
			if (!empty($result)) {
				while ($row = $result->fetch_assoc()) {
					$oldKey = $row[$key];
					$newKey = str_replace($oldDbPrefix, $dbPrefix, $oldKey);
					$update = $dbConnection->query("UPDATE $tablename SET $key = '$newKey' WHERE $key = '$oldKey'");
					if (!$update) {
						echo mysql_error();
					}
				}
			}
		}
	}

	// Configuration, Preferences

	public function getPreferences($type)
	{
		$preferences = parent::getPreferences($type);

		if ($type != 'theme') {
			$preferences[] = $this->_getLangPreference();
		}
		return $preferences;
	}

	private function _getLangPreference()
	{
		$preference = array(
			'name' => 'lang', 'label' => 'Language',
			'type' => 'list', 'defaultValue' => 'auto',
			'options' => array(
				array('value' => 'auto', 'label' => 'auto'),
				array('value' => 'en_US', 'label' => 'en_US')
			)
		);
		foreach ($this->_getLocales() as $locale) {
			$preference['options'][] = array('value' => $locale, 'label' => $locale);
		}
		return $preference;
	}

	private function _getLocales()
	{
		$locales = array();
		foreach (Ld_Files::getFiles($this->getAbsolutePath() . '/wp-content/languages') as $mo) {
			if (strpos($mo, 'continents-cities') === false) {
				$locales[] = str_replace('.mo', '', $mo);
			}
		}
		return $locales;
	}

	public function getConfiguration()
	{
		return $this->serviceRequest('getOptions');
	}

	public function setConfiguration($configuration, $type = 'general')
	{
		if ($type == 'general') {
			$type = 'configuration';
		}

		$options = array();
		foreach ($this->getPreferences($type) as $preference) {
			$preference =  is_object($preference) ? $preference->toArray() : $preference;
			$name = $preference['name'];
			$value = isset($configuration[$name]) ? $configuration[$name] : null;
			$options[$name] = $value;
		}
		$this->serviceRequest('setOptions', $options);

		if (isset($configuration['name']) && isset($this->instance)) {
			$this->instance->setInfos(array('name' => $configuration['name']))->save();
		}

		if (isset($configuration['lang']) && isset($this->instance)) {
			$this->instance->setInfos(array('locale' => $configuration['lang']))->save();
		}

		return $this->getConfiguration();
	}

	public function getThemes()
	{
		if (empty($this->themes)) {
			$this->themes = $this->serviceRequest('getThemes');
		}
		return $this->themes;
	}

	public function setTheme($stylesheet)
	{
		return $this->serviceRequest('setTheme', array('id' => $stylesheet));
	}

	public function getCustomCss()
	{
		return $this->serviceRequest('getCustomCss');
	}

	public function setCustomCss($css = '')
	{
		return $this->serviceRequest('setCustomCss', $css);
	}

	// Users and Roles

	public $roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');

	public $defaultRole = 'subscriber';

	public function getUsers()
	{
		return $this->serviceRequest('getUsers');
	}

	public function getRoles()
	{
		return $this->roles;
	}

	public function getUserRoles()
	{
		return $this->serviceRequest('getUserRoles');
	}

	public function setUserRoles($roles)
	{
		return $this->serviceRequest('setUserRoles', $roles);
	}

	// Service

	public function getSecret()
	{
		$infos = $this->instance->getInfos();
		if (empty($infos['secret'])) {
			$infos['secret'] = Ld_Auth::generatePhrase(32);
			$this->instance->setInfos(array('secret' => $infos['secret']))->save();
		}
		return $infos['secret'];
	}

	public function getServiceUri($method)
	{
		return $this->getSite()->getBaseUrl() . $this->getInstance()->getPath() . "/ld-service.php?method=$method";
	}

	public function serviceRequest($method, $params = array())
	{
		Ld_Files::log("Wordpress:serviceRequest", "$method");
		if (empty($this->httpClient)) {
			$this->httpClient = new Zend_Http_Client();
			if (isset($_COOKIE['ld-auth'])) {
				$this->httpClient->setCookie('ld-auth', stripslashes($_COOKIE['ld-auth']));
			}
			$this->httpClient->setCookie('ld-secret', $this->getSecret());
		}

		$this->httpClient->setUri( $this->getServiceUri($method) );
		if (!empty($params)) {
			$this->httpClient->setRawData(Zend_Json::encode($params),' application/json');
			$this->httpClient->setMethod('POST');
		}
		$response = $this->httpClient->request();

		$body = $response->getBody();
		if (empty($body)) {
			return true;
		}
		try {
			$result = Zend_Json::decode($body);
		} catch (Exception $e) {
			echo htmlspecialchars('<response>' . $body . '</response>');
		}
		return $result;
	}

}

class Ld_Installer_Wordpress_Plugin extends Ld_Installer_Wordpress
{

	public function getParentInstance()
	{
		return $this->getSite()->getInstance( $this->getAbsolutePath() . "/../../.." );
	}

	public function getSecret()
	{
		return $this->getParentInstance()->getInstaller()->getSecret();
	}

	public function getServiceUri($method)
	{
		return $this->getParentInstance()->getAbsoluteUrl("/ld-service.php?method=$method");
	}

	public function install($preferences = array())
	{
		parent::install($preferences);
		return $this->serviceRequest('activatePlugin', $this->plugin_file);
	}

	public function uninstall()
	{
		return $this->serviceRequest('deactivatePlugin', $this->plugin_file);
		parent::uninstall();
	}

}
