<?php

class Ld_Installer_Bbpress extends Ld_Installer
{

	public function install($preferences = array())
	{
		parent::install($preferences);

		$this->create_config_file();
	}

	public function postInstall($preferences = array())
	{
		parent::postInstall($preferences);

		if (isset($preferences['lang'])) {
			$this->getInstance()->setInfos(array('locale' => $preferences['lang']))->save();
		}

		$this->serviceRequest('init', $preferences);
	}

	public function postUpdate()
	{
		parent::postUpdate();
		// post update must not includ bbpress files or rely on load_bp()
	}

	public function postMove()
	{
		parent::postMove();
		$this->serviceRequest('updateUrl');
	}

	public function create_config_file()
	{
		$cfg = "<?php\n";

		$cfg .= "require_once(dirname(__FILE__) . '/dist/prepend.php');\n";

		$cfg .= "define('DB_CHARSET', 'utf8');\n";
		$cfg .= "define('DB_COLLATE', '');\n";

		$cfg .= "define('BB_AUTH_KEY', '" . Ld_Auth::generatePhrase() . "');\n";
		$cfg .= "define('BB_SECURE_AUTH_KEY', '" . Ld_Auth::generatePhrase() . "');\n";
		$cfg .= "define('BB_LOGGED_IN_KEY', '" . Ld_Auth::generatePhrase() . "');\n";
		$cfg .= "define('BB_NONCE_KEY', '" . Ld_Auth::generatePhrase() . "');\n";

		Ld_Files::put($this->getAbsolutePath() . "/bb-config.php", $cfg);
	}

	// Operations

	public function restore($restoreFolder)
	{
		parent::restore($restoreFolder);

		$this->serviceRequest('updateUrl');
	}

	// Preferences

	public function getPreferences($type)
	{
		$preferences = parent::getPreferences($type);
		
		if ($type != 'theme') {
			$preferences[] = $this->_getLangPreference();
		}
		return $preferences;
	}

	public function getLocales()
	{
		$locales = array();
		foreach (Ld_Files::getFiles($this->getAbsolutePath() . '/my-languages') as $mo) {
			$locales[] = str_replace('.mo', '', $mo);
		}
		return $locales;
	}

	protected function _getLangPreference()
	{
		$preference = array(
			'name' => 'lang', 'label' => 'Locale',
			'type' => 'list', 'defaultValue' => 'auto',
			'options' => array(
				array('value' => 'auto', 'label' => 'auto'),
				array('value' => 'en_US', 'label' => 'en_US')
			)
		);
		foreach ($this->getLocales() as $locale) {
			$preference['options'][] = array('value' => $locale, 'label' => $locale);
		}
		return $preference;
	}

	// Configuration

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
			$preference = is_object($preference) ? $preference->toArray() : $preference;
			$option = $preference['name'];
			$value = isset($configuration[$option]) ? $configuration[$option] : null;
			$options[$name] = $value;
		}
		$this->serviceRequest('setOptions', $options);

		if (isset($configuration['short_name']) && isset($this->instance)) {
			$this->instance->setInfos(array('name' => $configuration['short_name']))->save();
		}
		if (isset($configuration['lang']) && isset($this->instance)) {
			$this->instance->setInfos(array('locale' => $configuration['lang']))->save();
		}

		return $this->getConfiguration();
	}

	// Themes

	public function getThemes()
	{
		if (empty($this->themes)) {
			$this->themes = $this->serviceRequest('getThemes');
		}
		return $this->themes;
	}

	public function setTheme($theme)
	{
		return $this->serviceRequest('setTheme', $theme);
	}

	public function getCustomCss()
	{
		return $this->serviceRequest('getCustomCss');
	}

	public function setCustomCss($css = '')
	{
		return $this->serviceRequest('setCustomCss', $css);
	}

	// Roles

	public $roles = array('keymaster', 'administrator', 'moderator', 'member');

	public $defaultRole = 'member';

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
