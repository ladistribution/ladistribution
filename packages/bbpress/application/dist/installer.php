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

		define('BB_INSTALLING', true);

		$this->load_bp();

		$params = array(
			'name' => $preferences['title'],
			'uri' => $this->getSite()->getBaseUrl() . $preferences['path'],
			'keymaster_user_login' => $preferences['administrator']['username'],
			'keymaster_user_email' => $preferences['administrator']['email'],
			'keymaster_user_type' => 'new',
			'forum_name' => 'First Forum'
		);

		$defaults = array(
			'custom_user_meta_table', 'custom_user_table', 'user_bbdb_charset', 'user_bbdb_collate',
			'user_bbdb_host', 'user_bbdb_name', 'user_bbdb_password', 'user_bbdb_user', 'wordpress_mu_primary_blog_id',
			'wp_auth_key', 'wp_auth_salt', 'wp_home', 'wp_logged_in_key', 'wp_logged_in_salt', 'wp_secure_auth_key',
			'wp_secure_auth_salt', 'wp_siteurl', 'wp_table_prefix',
			'toggle_2_0'
		);
		foreach ($defaults as $default) {
			$params[$default] = '';
		}

		$additionals = array(
			'_wpnonce' => bb_create_nonce('bbpress-installer'),
			'toggle_2_1' => 0, 'toggle_2_2' => 0, 'toggle_2_3' => 0,
			'forward_3_1' => 'Complete the installation',
			'step' => '4'
		);
		$params = array_merge($params, $additionals);

		$this->httpClient = new Zend_Http_Client();
		$this->httpClient->setCookieJar();

		$this->httpClient->setUri($this->getInstance()->getUrl() . '/bb-admin/install.php');
		$this->httpClient->setParameterPost($params);
		$response = $this->httpClient->request('POST');

		$activate_plugins = array('core#ld.php', 'core#ld.ui.php', 'core#ld.auth.php', 'core#ld.css.php', 'core#akismet.php');
		foreach ($activate_plugins as $plugin) {
			bb_activate_plugin($plugin);
		}
		bb_update_option( 'active_plugins', $activate_plugins );
	}

	public function postUpdate()
	{
		$this->load_bp();

		$active_plugins = bb_get_option('active_plugins');

		// if current <= 1.0-2-39-3
		if (!in_array('core#akismet.php', $active_plugins)) {
			bb_activate_plugin('core#akismet.php');
			$active_plugins[] = 'core#akismet.php';
			bb_update_option( 'active_plugins', $active_plugins );
		}
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

	public function load_bp()
	{
		if (empty($this->loaded)) {
			global $bbdb, $wp_users_object;
			require_once($this->getAbsolutePath() . '/bb-load.php');
			require_once($this->getAbsolutePath() . '/bb-admin/includes/functions.bb-plugin.php');
			$this->bbdb = $bbdb;
			$this->loaded = true;
		}
	}

	// Operations

	public function restore($restoreFolder)
	{
		parent::restore($restoreFolder);

		$this->load_bp();

		bb_update_option('uri', $this->getSite()->getBaseUrl() . $this->getInstance()->getPath());
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
			'name' => 'lang', 'label' => 'Language',
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
		$this->load_bp();
		$metas_table = $this->bbdb->meta;
		$options = $this->bbdb->get_results("SELECT * FROM $metas_table WHERE object_type = 'bb_option' ORDER BY meta_key");
		$configuration = array();
		foreach ( (array) $options as $option) {
			if ( is_serialized($option->option_value) ) {
				continue;
			}
			$configuration[$option->meta_key] = $option->meta_value;
		}
		if (empty($configuration['short_name']) && isset($instance)) {
			$configuration['short_name'] = $this->getInstance()->getName();
		}
		return $configuration;
	}

	public function setConfiguration($configuration, $type = 'general')
	{
		if ($type == 'general') {
			$type = 'configuration';
		}
		$this->load_bp();
		foreach ($this->getPreferences($type) as $preference) {
			$preference = is_object($preference) ? $preference->toArray() : $preference;
			$option = $preference['name'];
			$value = isset($configuration[$option]) ? $configuration[$option] : null;
			bb_update_option($option, $value);
		}
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
		$this->load_bp();
		$bb_themes = bb_get_themes();
		$activetheme = bb_get_option('bb_active_theme');
		if (!$activetheme) {
			$activetheme = BB_DEFAULT_THEME;
		}
		$themes = array();
		foreach ($bb_themes as $id) {
			list($type, $name) = explode('#', $id);
			$folder = $type == 'user' ? 'my-templates' : 'bb-templates';
			$screenshot = $this->site->getBaseUrl() . $this->getPath() . '/' . $folder . '/' . $name . '/screenshot.png';
			$dir = $this->getAbsolutePath() . '/' . $folder . '/' . $name;
			$active = $activetheme == $id;
			$themes[$id] = compact('name', 'dir', 'screenshot', 'active');
		}
		return $themes;
	}

	public function setTheme($theme)
	{
		$this->load_bp();
		bb_update_option('bb_active_theme', $theme);
	}

	// Roles

	public $roles = array('keymaster', 'administrator', 'moderator', 'member', 'inactive');

	public $defaultRole = 'member';

	public function getRoles()
	{
		return $this->roles;
	}

	public function getUserRoles()
	{
		$this->load_bp();
		$roles = array();
		$users = $this->getSite()->getUsers();
		foreach ($users as $user) {
			$username = $user['username'];
			$roles[$username] = $this->defaultRole; // default
			$userdata = get_bb_user_by_login($username);
			if ($userdata) {
				$bb_user = new BP_User($userdata->ID);
				foreach ($this->roles as $role) {
					if (isset($bb_user->caps[$role]) && $bb_user->caps[$role]) {
						$roles[$username] = $role;
					}
				}
			}
		}
		return $roles;
	}

	public function setUserRoles($roles)
	{
		$this->load_bp();
		$current_user_roles = $this->getUserRoles();
		foreach ($roles as $username => $role) {
			if (isset($current_user_roles[$username]) && $current_user_roles[$username] == $role) {
				continue;
			}
			$userdata = get_bb_user_by_login($username);
			$bb_user = new BP_User($userdata->ID);
			$bb_user->set_role($role);
		}
	}

}
