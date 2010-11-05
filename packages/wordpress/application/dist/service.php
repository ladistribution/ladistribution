<?php

class Ld_Service_Wordpress
{

	public function getSite()
	{
		return Zend_Registry::get('site');
	}

	public function getApplication()
	{
		return Zend_Registry::get('application');
	}

	public function init($params)
	{
		$application = self::getApplication();

		extract($params);

		wp_check_mysql_version();
		wp_cache_flush();
		make_db_current_silent();
		populate_options();
		populate_roles();

		if (isset($user_name)) {
			// Create administrator
			$user_id = username_exists($user_name);
			if ( !$user_id ) {
				$user_id = wp_create_user($user_name, $user_password, $user_email);
			}
			// Update administrator password
			$user = get_userdata($user_id);
			$userdata = add_magic_quotes(get_object_vars($user));
			$userdata['user_pass'] = $user_hash;
			$user_id = wp_insert_user($userdata);
			// Email
			update_option('admin_email', $user_email);
		}


		// Options
		update_option('blogname', $title);
		update_option('siteurl', $url);
		update_option('home', $url);

		// Nice URLs
		if ($rewrite && got_mod_rewrite()) {
			global $wp_rewrite;
			$wp_rewrite->set_permalink_structure('/%year%/%monthnum%/%postname%/');
			$wp_rewrite->flush_rules();
		}

		wp_install_defaults($user_id);

		// Default Sidebar widgets
		$defaults = array(
			'wp_inactive_widgets' => array(),
			'primary-widget-area' => array(0 => 'search-2', 1 => 'pages', 2 => 'archives-2', 3 => 'categories-2', 4 => 'links', 5 => 'meta-2'),
			'array_version' => 3
		);
		update_option('sidebars_widgets', $defaults);

		// Activate plugins
		$plugins = array('ld.php', 'ld-ui.php', 'ld-auth.php', 'ld-css.php', 'akismet/akismet.php');
		foreach ($plugins as $plugin) {
			activate_plugin($plugin);
		}
		update_option('active_plugins', $plugins);

		self::setUserRoles(array($user_name => 'administrator'));

		if (isset($theme)) {
			self::setTheme(array('id' => $theme));
		}

		wp_cache_flush();
	}

	// Utilities

	public function updateUrl()
	{
		$site = self::getSite();
		$application = self::getApplication();

		wp_cache_flush();
		remove_filter('clean_url', 'qtrans_convertURL');
		update_option('siteurl', $site->getBaseUrl() . $application->getPath());
		update_option('home', $site->getBaseUrl() . $application->getPath());
		update_option('upload_path', $application->getAbsolutePath() . '/wp-content/uploads');
	}

	// Configuration

	public function getOptions()
	{
		global $wpdb;
		$options_table = $wpdb->options;
		$options = $wpdb->get_results("SELECT * FROM $options_table ORDER BY option_name");
		$configuration = array();
		foreach ( (array) $options as $option) {
			if ( is_serialized($option->option_value) ) {
				continue;
			}
			$configuration[$option->option_name] = $option->option_value;
		}

		$application = self::getApplication();
		if (empty($configuration['name']) && isset($application)) {
			$configuration['name'] = $application->getName();
		}

		return $configuration;
	}

	public function setOptions($params)
	{
		foreach ($params as $key => $value) {
			update_option($key, $value);
		}
	}

	// Themes

	public function getThemes()
	{
		$site = self::getSite();
		$application = self::getApplication();

		$themes = array();
		foreach (get_themes() as $theme) {
			$id = $theme['Stylesheet'];
			$name = $theme['Name'];
			$template = $theme['Template'];
			$folder = 'wp-content/themes/' . $theme['Stylesheet'];
			$dir = $application->getAbsolutePath() . '/' . $folder;
			$screenshot = $site->getBaseUrl() . $application->getPath() . '/' . $folder . '/' . $theme['Screenshot'];
			$active = get_current_theme() == $theme['Name'];
			$themes[$id] = compact('name', 'template', 'dir', 'screenshot', 'active');
		}
		return $themes;
	}

	public function setTheme($params)
	{
		$id = $params['id'];
		$themes = self::getThemes();
		$theme = $themes[$id];
		$stylesheet = $id;
		switch_theme($theme['template'], $stylesheet);
		update_option('current_theme', $theme['name']);
	}

	// Users and Roles

	protected static $roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');

	protected static $defaultRole = 'subscriber';

	public function getUsers()
	{
		$site = self::getSite();
		$application = self::getApplication();

		$users = array();

		$dbPrefix = $application->getDbPrefix();
		$dbConnection = $application->getDbConnection('php');
		$result = $dbConnection->query("SELECT * FROM {$dbPrefix}users");

		while ($wp_user = $result->fetch_object()) {
			$user = $site->getUser($wp_user->user_login);
			if (!empty($user)) {
				$users[] = $user;
			}
		}

		return $users;
	}

	public function getUserRoles()
	{
		$roles = array();
		$users = self::getUsers();
		foreach ($users as $user) {
			$username = $user['username'];
			$roles[$username] = self::$defaultRole;
			$userdata = get_userdatabylogin($username);
			$wp_user = new WP_User($userdata->ID);
			foreach (self::$roles as $role) {
				if (isset($wp_user->caps[$role]) && $wp_user->caps[$role]) {
					$roles[$username] = $role;
				}
			}
		}
		return $roles;
	}

	public function setUserRoles($roles)
	{
		$current_user_roles = self::getUserRoles();
		foreach ($roles as $username => $role) {
			if (isset($current_user_roles[$username]) && $current_user_roles[$username] == $role) {
				continue;
			}
			$userdata = get_userdatabylogin($username);
			$wp_user = new WP_User($userdata->ID);
			$wp_user->set_role($role);
		}
	}

}
