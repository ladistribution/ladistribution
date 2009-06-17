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
		update_option('siteurl', $this->site->getBaseUrl() . $preferences['path']);
		update_option('home', $this->site->getBaseUrl() . $preferences['path']);

		if (constant('LD_REWRITE')) {
			$this->enable_clean_urls();
		}

		$this->install_defaults($user_id);

		$this->populate_sidebar_widgets();

		update_option('active_plugins', array());

		activate_plugin('ld.php');
		activate_plugin('ld-ui.php');
		activate_plugin('ld-auth.php');

		if (isset($preferences['theme'])) {
			$this->setTheme($preferences['theme']);
		}

		wp_cache_flush();
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

		Ld_Files::put($this->absolutePath . "/wp-config.php", $cfg);
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
			$screenshot = $this->site->getBaseUrl() . $this->path . '/wp-content' . $theme['Stylesheet Dir'] . '/' . $theme['Screenshot'];
			$active = $current_theme == $theme['Name'];
			$themes[$id] = compact('name', 'screenshot', 'active');
		}
		return $themes;
	}
    
	public function getBackupDirectories()
	{
		$this->load_wp();

		function escape($string)
		{
			$string = str_replace('\\', '\\\\', $string);
			$string = addcslashes($string, '"');
			return '"' . $string . '"';
		}

		Ld_Files::createDirIfNotExists($this->tmpFolder . '/tables');

		// Generate SQL schema
		$fp = fopen($this->tmpFolder . "/tables/schema.sql", "w");
		foreach ($this->wpdb->tables as $table) {
			$tablename = $this->wpdb->$table;
			$drop = "DROP TABLE IF EXISTS `" . $tablename . "`;\n";
			$result = $this->wpdb->get_results("SHOW CREATE TABLE $tablename", ARRAY_N);
			$create = $result[0][1] . ";\n";
			fwrite($fp, $drop);
			fwrite($fp, $create);
		}
		fclose($fp);

		// Generate data CSVs
		foreach ($this->wpdb->tables as $table) {
			$results = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->$table, ARRAY_N);
			$fp = fopen($this->tmpFolder . "/tables/$table.csv", "w");
			foreach ( (array) $results as $result) {
				$result = array_map("escape", $result);
				$line = implode(";", $result) . "\n";
				fwrite($fp, $line);
			}
			fclose($fp);
		}
		
		return array(
			'tables' => $this->tmpFolder . '/tables/',
			'uploads' => $this->absolutePath . '/wp-content/uploads/'
		);
	}

	public function restore($filename, $absolute = false)
	{
		parent::restore($filename, $absolute);

		$this->load_wp();
        
		if (file_exists($this->tmpFolder . '/uploads')) {
			Ld_Files::copy($this->tmpFolder . '/uploads', $this->absolutePath . '/wp-content/uploads');
		}

		foreach ($this->wpdb->tables as $table) {
			$filename = $this->tmpFolder . '/tables/' . $table . '.csv';
			$tablename = $this->wpdb->$table;
			$query = "LOAD DATA LOCAL INFILE '$filename'
			REPLACE INTO TABLE $tablename
			FIELDS TERMINATED BY ';'
			ENCLOSED BY '\"'
			ESCAPED BY '\\\\'
			LINES TERMINATED BY '\n'"; //  IGNORE 1 LINES;
			$result = $this->wpdb->query($query);
		}

		update_option('siteurl', $this->site->getBaseUrl() . $this->instance->path);
		update_option('home', $this->site->getBaseUrl() . $this->instance->path);

		wp_cache_flush();

		Ld_Files::unlink($this->tmpFolder);
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
				$template_dir = $this->absolutePath . '/wp-content' . $theme['Stylesheet Dir'];
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
		$users = $this->site->getUsers();
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

			require_once $this->absolutePath . "/wp-load.php";
			require_once $this->absolutePath . "/wp-admin/includes/upgrade.php";
			require_once $this->absolutePath . "/wp-admin/includes/plugin.php";
			require_once $this->absolutePath . "/wp-includes/theme.php";

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
			insert_with_markers($this->absolutePath . "/.htaccess", 'WordPress', $rules );
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
