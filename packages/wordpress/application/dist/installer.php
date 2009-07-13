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
		// this is not possible to do that here, due to multiple instances update
		// $this->load_wp();
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
			$screenshot = $this->getSite()->getBaseUrl() . $this->getPath() .
			    '/wp-content' . $theme['Stylesheet Dir'] . '/' . $theme['Screenshot'];
			$active = $current_theme == $theme['Name'];
			$themes[$id] = compact('name', 'screenshot', 'active');
		}
		return $themes;
	}
    
	public function getBackupDirectories()
	{
		parent::getBackupDirectories();
		$this->_backupDirectories['uploads'] = $this->getAbsolutePath() . '/wp-content/uploads/';
		return $this->_backupDirectories;
	}

	public function restore($filename, $absolute = false)
	{
		parent::restore($filename, $absolute);

		if (file_exists($this->getBackupFolder() . '/uploads')) {
			Ld_Files::copy($this->getBackupFolder() . '/uploads', $this->getAbsolutePath() . '/wp-content/uploads');
		}

		$this->load_wp();

		update_option('siteurl', $this->getSite()->getBaseUrl() . $this->getPath());
		update_option('home', $this->getSite()->getBaseUrl() . $this->getPath());

		wp_cache_flush();

		Ld_Files::unlink($this->getBackupFolder());
	}
	
	public function setTheme($theme)
	{
		$this->load_wp();
		switch_theme($theme, $theme);
	}

	// public function uninstall()
	// {
	// 	$this->load_wp();
	// 	
	// 	foreach ($this->wpdb->tables as $table) {
	// 		$tablename = $this->wpdb->$table;
	// 		$result = $this->wpdb->query("DROP TABLE $tablename;");
	// 	}
	// 
	// 	parent::uninstall();
	// }

	public function getPreferences($type)
	{
		switch ($type) {
			case 'theme':
				return $this->getThemePreferences();
			default:
				$preferences = parent::getPreferences($type);
				return $preferences;
		}
	}

	public function getThemePreferences()
	{
		$this->load_wp();
		$wp_themes = get_themes();
		$current_theme = get_current_theme();
		foreach ($wp_themes as $theme) {
			if ($current_theme == $theme['Name']) {
				$template_dir = $this->getAbsolutePath() . '/wp-content' . $theme['Stylesheet Dir'];
				break;	
			}
		}
		if (file_exists($template_dir) && file_exists($template_dir . '/dist/manifest.xml')) {
			$template_installer = new Ld_Installer(array('dir' => $template_dir));
			return $template_installer->getPreferences('configuration');
		}
		return array();
	}

	public function getConfiguration()
	{
		$this->load_wp();
		$options_table = $this->wpdb->options;
		$options = $this->wpdb->get_results("SELECT * FROM $options_table ORDER BY option_name");
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
		if ($type == 'general') {
			$type = 'configuration';
		}
		$this->load_wp();
		foreach ($this->getPreferences($type) as $preference) {
			$preference = $preference->toArray();
			$option = $preference['name'];
			$value = isset($configuration[$option]) ? $configuration[$option] : null;
			update_option($option, $value);
		}
		if (isset($configuration['blogname']) && isset($this->instance)) {
			$this->instance->setInfos(array('name' => $configuration['blogname']))->save();
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

			global $wpdb, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem, $is_apache;

			// fix 'Wrong datatype for second argument in wp-includes/widgets.php on lines 607, 695'
			global $_wp_deprecated_widgets_callbacks;

			require_once $this->getAbsolutePath() . "/wp-load.php";
			require_once $this->getAbsolutePath() . "/wp-admin/includes/upgrade.php";
			require_once $this->getAbsolutePath() . "/wp-admin/includes/plugin.php";
			require_once $this->getAbsolutePath() . "/wp-includes/theme.php";

			$this->wp_rewrite = $wp_rewrite;
			$this->wpdb = $wpdb;

			$this->loaded = true;

		}
	}

	// Add the .htaccess and active clean URLs
	function enable_clean_urls()
	{
		$wp_rewrite = $this->wp_rewrite;
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
			global $wpdb, $wp_version, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem, $wp_roles;
			global $_wp_deprecated_widgets_callbacks;
			require_once $this->absolutePath . "/../../../wp-load.php";
			require_once $this->absolutePath . "/../../../wp-admin/includes/plugin.php";
			$this->loaded = true;
		}
	}

	public function install($preferences = array())
	{
		parent::install($preferences);
		$this->load_wp();
		activate_plugin($this->plugin_file);
	}

	public function uninstall()
	{
		$this->load_wp();
		deactivate_plugins($this->plugin_file);
		parent::uninstall();
	}

}
