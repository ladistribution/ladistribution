<?php

class Ld_Installer_Wordpress extends Ld_Installer
{

	function install($preferences = array())
	{
		parent::install($preferences);

		$this->create_config_file();
	}

	function postInstall($preferences = array())
	{
		parent::postInstall($preferences);

		define('WP_INSTALLING', true);

		if (isset($preferences['lang'])) {
			$this->getInstance()->setInfos(array('locale' => $preferences['lang']))->save();
		}

		$this->load_wp();

		wp_check_mysql_version();
		wp_cache_flush();
		make_db_current_silent();
		populate_options();
		populate_roles();

		if (isset($preferences['administrator'])) {
			$preferences['admin_username'] = $preferences['administrator']['username'];
			$preferences['admin_email'] = $preferences['administrator']['email'];
			$preferences['admin_password'] = '';
		}

		$user_name     = $preferences['admin_username'];
		$user_password = $preferences['admin_password'];
		$user_email    = $preferences['admin_email'];

		$user_id = username_exists($user_name);
		if ( !$user_id ) {
			$user_id = wp_create_user($user_name, $user_password, $user_email);
		}

		$this->setUserRoles(array($user_name => 'administrator'));

		update_option('admin_email', $user_email);
		update_option('blogname', $preferences['title']);
		update_option('siteurl', $this->getSite()->getBaseUrl() . $preferences['path']);
		update_option('home', $this->getSite()->getBaseUrl() . $preferences['path']);

		if (constant('LD_REWRITE')) {
			$this->enable_clean_urls();
		}

		$this->install_defaults($user_id);

		$this->populate_sidebar_widgets();

		update_option('active_plugins', array());

		activate_plugin('ld.php');
		activate_plugin('ld-ui.php');
		activate_plugin('ld-auth.php');
		activate_plugin('ld-css.php');

		if (isset($preferences['theme'])) {
			$this->setTheme($preferences['theme']);
		}

		wp_cache_flush();
    }

	function postUpdate()
	{
		$this->httpClient = new Zend_Http_Client();
		$this->httpClient->setCookieJar();
		$this->httpClient->setUri($this->getSite()->getBaseUrl() . $this->getInstance()->getPath() . '/wp-admin/upgrade.php?step=1');
		$response = $this->httpClient->request('GET');
	}

	function create_config_file()
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

	public function getThemes()
	{
		$this->load_wp();
		$wp_themes = get_themes();
		$current_theme = get_current_theme();
		$themes = array();
		foreach ($wp_themes as $theme) {
			$id = $theme['Stylesheet'];
			$name = $theme['Name'];
			$template = $theme['Template'];
			$folder = 'wp-content/themes/' . $theme['Stylesheet'];
			$dir = $this->getAbsolutePath() . '/' . $folder;
			$screenshot = $this->getSite()->getBaseUrl() .
				$this->getPath() . '/' . $folder . '/' . $theme['Screenshot'];
			$active = $current_theme == $theme['Name'];
			$themes[$id] = compact('name', 'template', 'dir', 'screenshot', 'active');
		}
		return $themes;
	}

	public function getBackupDirectories()
	{
		parent::getBackupDirectories();
		$this->_backupDirectories['uploads'] = $this->getAbsolutePath() . '/wp-content/uploads/';
		return $this->_backupDirectories;
	}

	public function restore($restoreFolder)
	{
		parent::restore($restoreFolder);

		$this->_fixDb();

		$this->load_wp();

		wp_cache_flush();

		if (file_exists($this->getRestoreFolder() . '/uploads')) {
			Ld_Files::copy($this->getRestoreFolder() . '/uploads', $this->getAbsolutePath() . '/wp-content/uploads');
		}

		$this->_fixUrl();

		Ld_Files::unlink($this->getRestoreFolder());
	}

	public function postMove()
	{
		$this->load_wp();
		$this->_fixUrl();
	}

	protected function _fixUrl()
	{
		remove_filter('clean_url', 'qtrans_convertURL');
		update_option('siteurl', $this->getSite()->getBaseUrl() . $this->getInstance()->getPath());
		update_option('home', $this->getSite()->getBaseUrl() . $this->getInstance()->getPath());
		update_option('upload_path', $this->getAbsolutePath() . '/wp-content/uploads');
	}

	protected function _fixDb()
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

	public function setTheme($stylesheet)
	{
		$this->load_wp();
		$themes = $this->getThemes();
		$theme = $themes[$stylesheet];
		switch_theme($theme['template'], $stylesheet);
	}

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
		foreach (Ld_Files::getFiles($this->getAbsolutePath() . '/wp-content/languages') as $mo) {
			if (strpos($mo, 'continents-cities') === false) {
				$locales[] = str_replace('.mo', '', $mo);
			}
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

	public function getConfiguration()
	{
		global $wpdb;
		$this->load_wp();
		$options_table = $wpdb->options;
		$options = $wpdb->get_results("SELECT * FROM $options_table ORDER BY option_name");
		$configuration = array();
		foreach ( (array) $options as $option) {
			if ( is_serialized($option->option_value) ) {
				continue;
			}
			$configuration[$option->option_name] = $option->option_value;
		}
		return $configuration;
	}

	public function setConfiguration($configuration, $type = 'general')
	{
		$this->load_wp();

		if ($type == 'general') {
			$type = 'configuration';
		}

		foreach ($this->getPreferences($type) as $preference) {
			$preference =  is_object($preference) ? $preference->toArray() : $preference;
			$option = $preference['name'];
			$value = isset($configuration[$option]) ? $configuration[$option] : null;
			update_option($option, $value);
		}

		if (isset($configuration['blogname']) && isset($this->instance)) {
			$this->instance->setInfos(array('name' => $configuration['blogname']))->save();
		}

		if (isset($configuration['lang']) && isset($this->instance)) {
			$this->instance->setInfos(array('locale' => $configuration['lang']))->save();
		}

		return $this->getConfiguration();
	}

	public $roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');

	public $defaultRole = 'subscriber';

	public function getRoles()
	{
		return $this->roles;
	}

	public function getUserRoles()
	{
		$this->load_wp();
		$roles = array();
		$users = $this->getSite()->getUsers();
		foreach ($users as $user) {
			$username = $user['username'];
			$roles[$username] = $this->defaultRole; // default
			$userdata = get_userdatabylogin($username);
			$wp_user = new WP_User($userdata->ID);
			foreach ($this->roles as $role) {
				if (isset($wp_user->caps[$role]) && $wp_user->caps[$role]) {
					$roles[$username] = $role;
				}
			}
		}
		return $roles;
	}

	public function setUserRoles($roles)
	{
		$this->load_wp();
		$current_user_roles = $this->getUserRoles();
		foreach ($roles as $username => $role) {
			if (isset($current_user_roles[$username]) && $current_user_roles[$username] == $role) {
				continue;
			}
			$userdata = get_userdatabylogin($username);
			$wp_user = new WP_User($userdata->ID);
			$wp_user->set_role($role);
		}
	}

	function load_wp()
	{
		if (empty($this->loaded)) {
			define('WP_LD_INSTALLER', true);
			global $wpdb, $wp_embed;
			require_once $this->getAbsolutePath() . "/wp-load.php";
			require_once $this->getAbsolutePath() . "/wp-admin/includes/upgrade.php";
			require_once $this->getAbsolutePath() . "/wp-admin/includes/plugin.php";
			require_once $this->getAbsolutePath() . "/wp-includes/theme.php";
			$globals = array_keys( get_defined_vars() );
			foreach ($globals as $key) {
				if (empty($GLOBALS[$key])) {
					$GLOBALS[$key] = $$key;
				}
			}
			$this->loaded = true;

		}
	}

	// Add the .htaccess and active clean URLs
	function enable_clean_urls()
	{
		global $wp_rewrite;
		if (got_mod_rewrite()) {
			$wp_rewrite->set_permalink_structure('/%year%/%monthnum%/%postname%/');
			$rules = explode( "\n", $wp_rewrite->mod_rewrite_rules() );
			insert_with_markers($this->getAbsolutePath() . "/.htaccess", 'WordPress', $rules );
		}
	}

	function install_defaults($user_id)
	{
		wp_install_defaults($user_id);
	}

	function populate_sidebar_widgets()
	{
		$defaults = array(
			'sidebar-1' => array(
				'search', 'pages', 'archives', 'categories', 'links', 'meta'
			),
			'sidebar-2' => array(
			),
			'array_version' => 3
		);

		update_option('sidebars_widgets', $defaults);
	}

}

class Ld_Installer_Wordpress_Plugin extends Ld_Installer
{

	private function load_wp()
	{
		if (empty($this->loaded)) {
			define('WP_LD_INSTALLER', true);
			global $wpdb, $wp_embed;
			require_once $this->getAbsolutePath() . "/wp-load.php";
			require_once $this->getAbsolutePath() . "/wp-admin/includes/plugin.php";
			$globals = array_keys( get_defined_vars() );
			foreach ($globals as $key) {
				if (empty($GLOBALS[$key])) {
					$GLOBALS[$key] = $$key;
				}
			}
			$this->loaded = true;
		}
	}

	public function install($preferences = array())
	{
		parent::install($preferences);
		$this->load_wp();
		wp_cache_delete('plugins', 'plugins');
		activate_plugin($this->plugin_file);
	}

	public function uninstall()
	{
		$this->load_wp();
		deactivate_plugins($this->plugin_file);
		parent::uninstall();
	}

}
