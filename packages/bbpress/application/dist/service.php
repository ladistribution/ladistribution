<?php

class Ld_Service_Bbpress
{

	public function getSite()
	{
		return Zend_Registry::get('site');
	}

	public function getApplication()
	{
		return Zend_Registry::get('application');
	}

	public function init($preferences)
	{
		$site = self::getSite();
		$application = self::getApplication();

		$params = array(
			'name' => $preferences['title'],
			'uri' => $application->getAbsoluteUrl(''),
			'keymaster_user_login' => $preferences['administrator']['username'],
			// 'keymaster_user_email' => $preferences['administrator']['email'],
			'keymaster_user_email' => 'null@null.ladistribution.net',
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

		$httpClient = new Zend_Http_Client();
		$httpClient->setCookieJar();

		$httpClient->setUri($application->getAbsoluteUrl('/bb-admin/install.php'));
		$httpClient->setParameterPost($params);
		$response = $httpClient->request('POST');

		$activate_plugins = array('core#ld.php', 'core#ld.ui.php', 'core#ld.auth.php', 'core#ld.css.php', 'core#akismet.php');
		foreach ($activate_plugins as $plugin) {
			bb_activate_plugin($plugin);
		}
		bb_update_option( 'active_plugins', $activate_plugins );

		bb_update_option( 'from_email', $preferences['administrator']['email'] );

		bb_update_option( 'avatars_show', 1 );
		bb_update_option( 'avatars_rating', 'g' );
		bb_update_option( 'avatars_default', 'default' );

		return 'ok';
	}

	// Utilities

	public function updateUrl()
	{
		$site = self::getSite();
		$application = self::getApplication();

		$uri = $site->getBaseUrl() . $application->getPath();
		bb_update_option('uri', $uri);

		return $uri;
	}

	// Configuration

	public function getOptions()
	{
		global $bbdb;

		$site = self::getSite();
		$application = self::getApplication();

		$metas_table = $bbdb->meta;
		$options = $bbdb->get_results("SELECT * FROM $metas_table WHERE object_type = 'bb_option' ORDER BY meta_key");
		$configuration = array();
		foreach ( (array) $options as $option) {
			if ( is_serialized($option->option_value) ) {
				continue;
			}
			$configuration[$option->meta_key] = $option->meta_value;
		}
		if (empty($configuration['short_name']) && $application) {
			$configuration['short_name'] = $application->getName();
		}
		if (empty($configuration['lang']) && $application) {
			$configuration['lang'] = $application->getLocale();
		}

		return $configuration;
	}

	public function setOptions($params)
	{
		foreach ($params as $key => $value) {
			update_option($key, '');
			update_option($key, $value);
		}
	}

	// Themes

	public function getThemes()
	{
		$site = self::getSite();
		$application = self::getApplication();

		$bb_themes = bb_get_themes();
		$activetheme = bb_get_option('bb_active_theme');
		if (!$activetheme) {
			$activetheme = BB_DEFAULT_THEME;
		}

		$themes = array();
		foreach ($bb_themes as $id) {
			list($type, $name) = explode('#', $id);
			$folder = $type == 'user' ? 'my-templates' : 'bb-templates';
			$screenshot = $site->getBaseUrl() . $application->getPath() . '/' . $folder . '/' . $name . '/screenshot.png';
			$dir = $application->getAbsolutePath() . '/' . $folder . '/' . $name;
			$active = $activetheme == $id;
			$themes[$id] = compact('name', 'dir', 'screenshot', 'active');
		}

		return $themes;
	}

	public function setTheme($theme)
	{
		bb_update_option('bb_active_theme', $theme);
	}

	public function getCustomCss()
	{
		return bb_get_option('ld_custom_css');
	}

	public function setCustomCss($css)
	{
		bb_update_option('ld_custom_css', $css);
		return bb_get_option('ld_custom_css');
	}

	// Users and Roles

	protected static $roles = array('keymaster', 'administrator', 'moderator', 'member');

	protected static $defaultRole = 'member';

	public function getUserRoles()
	{
		$site = self::getSite();

		$roles = array();
		$users = $site->getUsers();
		foreach ($users as $user) {
			$username = $user['username'];
			$roles[$username] = self::$defaultRole; // default
			$userdata = Ld_Bbpress_Auth::get_bb_user_by_login($username);
			if ($userdata) {
				$bb_user = new BP_User($userdata->ID);
				foreach (self::$roles as $role) {
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
		$current_user_roles = self::getUserRoles();
		foreach ($roles as $username => $role) {
			if (isset($current_user_roles[$username]) && $current_user_roles[$username] == $role) {
				continue;
			}
			$userdata = Ld_Bbpress_Auth::get_bb_user_by_login($username);
			$bb_user = new BP_User($userdata->ID);
			$bb_user->set_role($role);
		}
	}

}
