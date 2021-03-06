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

		$this->handleRewrite();
    }

	public function postUpdate()
	{
		$this->httpClient = new Zend_Http_Client();
		$this->httpClient->setCookieJar();
		$this->httpClient->setUri($this->getSite()->getBaseUrl() . $this->getInstance()->getPath() . '/wp-admin/upgrade.php?step=1');
		$response = $this->httpClient->request('GET');

		$this->handleRewrite();
	}

	public function postMove()
	{
		$this->serviceRequest('updateUrl');

		$this->handleRewrite();
	}

	public function postUninstall()
	{
		if (defined('LD_NGINX') && constant('LD_NGINX')) {
			$nginxDir = $this->getSite()->getDirectory('dist') . '/nginx';
			Ld_Files::rm($nginxDir . "/" . $this->getInstance()->getId() . ".conf");
		}
	}

	public function handleRewrite()
	{
		if (defined('LD_NGINX') && constant('LD_NGINX')) {
			// Generate configuration
			$path = $this->getSite()->getPath() . '/' . $this->getPath() . '/';
			$nginxConf  = 'location {PATH} {' . "\n";
			$nginxConf .= '  try_files $uri $uri/ {PATH}index.php$is_args$args;' . "\n";
			$nginxConf .= '}' . "\n";
			$nginxConf = str_replace('{PATH}', $path, $nginxConf);
			// Write configuration
			$nginxDir = $this->getSite()->getDirectory('dist') . '/nginx';
			Ld_Files::ensureDirExists($nginxDir);
			Ld_Files::put($nginxDir . "/" . $this->getInstance()->getId() . ".conf", $nginxConf);
		}
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

		if (Ld_Files::exists($this->getRestoreFolder() . '/uploads')) {
			Ld_Files::copy($this->getRestoreFolder() . '/uploads', $this->getAbsolutePath() . '/wp-content/uploads');
		}

		$this->serviceRequest('updateUrl');
		$this->serviceRequest('flushCache');

		Ld_Files::rm($this->getRestoreFolder());
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
		$configuration = $this->serviceRequest('getOptions');
		return $configuration;
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

	public function getColorSchemes()
	{
		return array('base', 'bars', 'panels');
	}

	// Users and Roles

	public $roles = array('subscriber', 'contributor', 'author', 'editor', 'administrator');

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
