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

		define('BB_INSTALLING', true);
		$this->load_bp();

		$params = array(
			'name' => $preferences['title'],
			'uri' => $this->site->getBaseUrl() . $preferences['path'],
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

		$this->httpClient->setUri($this->instance->getUrl() . '/bb-admin/install.php');
		$this->httpClient->setParameterPost($params);
		$response = $this->httpClient->request('POST');
		// echo $response->getBody();
		
		$activate_plugins = array('core#ld.ui.php', 'core#ld.auth.php');
		foreach ($activate_plugins as $plugin) {
			bb_activate_plugin($plugin);
		}
		bb_update_option( 'active_plugins', $activate_plugins );
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

		Ld_Files::put($this->absolutePath . "/bb-config.php", $cfg);
	}

	public function load_bp()
	{
		if (empty($this->loaded)) {
			require_once($this->absolutePath . '/bb-load.php');
			require_once($this->absolutePath . '/bb-admin/includes/functions.bb-plugin.php');
			$this->bbdb = $bbdb;
			$this->loaded = true;
		}
	}

	public function getPreferences($type)
	{
		switch ($type) {
			case 'theme':
				return array();
			default:
				$preferences = parent::getPreferences($type);
				return $preferences;
		}
	}

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
		return $configuration;
	}

	public function setConfiguration($configuration, $type = 'general')
	{
		if ($type == 'general') {
			$type = 'configuration';
		}
		$this->load_bp();
		foreach ($this->getPreferences($type) as $preference) {
			$preference = $preference->toArray();
			$option = $preference['name'];
			$value = isset($configuration[$option]) ? $configuration[$option] : null;
			bb_update_option($option, $value);
		}
		if (isset($configuration['name']) && isset($this->instance)) {
			$this->instance->setInfos(array('name' => $configuration['name']))->save();
		}
		return $this->getConfiguration();
	}

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
			$screenshot = $this->site->getBaseUrl() . $this->path . '/' . $folder . '/' . $name . '/screenshot.png';
			$active = $activetheme == $id;
			$themes[$id] = compact('name', 'screenshot', 'active');
		}
		return $themes;
	}

	public function setTheme($theme)
	{
		$this->load_bp();
		bb_update_option('bb_active_theme', $theme);
	}

}