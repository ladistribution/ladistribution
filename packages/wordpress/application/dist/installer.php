<?php

class Installer_Wordpress extends Ld_Installer
{

	function install($preferences = array())
	{
		parent::install($preferences);

		$this->create_config_file();

		$this->load_wp();

		wp_check_mysql_version();
		wp_cache_flush();
		make_db_current_silent();
		populate_options();
		populate_roles();

		$user_name = $preferences['admin_username'];
		$user_email = $preferences['admin_email'];
		$user_password = $preferences['admin_password'];

		$user_id = username_exists($user_name);
		if ( !$user_id ) {
			$user_id = wp_create_user($user_name, $user_password, $user_email);
		}
		$user = new WP_User($user_id);
		$user->set_role('administrator');

		update_option('admin_email', $user_email);
		update_option('blogname', $preferences['title']);
		update_option('siteurl', LD_BASE_URL . $preferences['path']);
		update_option('home', LD_BASE_URL . $preferences['path']);

		if (true === LD_REWRITE) {
			$this->enable_clean_urls();
		}

		$this->populate_database($user_id);

		activate_plugin('ld.php');

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

		$cfg .= "define('DB_NAME', LD_DB_NAME);\n";
		$cfg .= "define('DB_USER', LD_DB_USER);\n";
		$cfg .= "define('DB_PASSWORD', LD_DB_PASSWORD);\n";
		$cfg .= "define('DB_HOST', LD_DB_HOST);\n";
		$cfg .= "define('DB_CHARSET', 'utf8');\n";
		$cfg .= "define('DB_COLLATE', '');\n";

		$cfg .= "define('AUTH_KEY', '" . $this->_generate_phrase() . "');\n";
		$cfg .= "define('SECURE_AUTH_KEY', '" . $this->_generate_phrase() . "');\n";
		$cfg .= "define('LOGGED_IN_KEY', '" . $this->_generate_phrase() . "');\n";
		$cfg .= "define('NONCE_KEY', '" . $this->_generate_phrase() . "');\n";

		$cfg .= '$' . "table_prefix = '" . $this->dbPrefix . "';\n";

		$cfg .= "require_once(ABSPATH . 'wp-settings.php');\n";

		file_put_contents($this->absolutePath . "/wp-config.php", $cfg);
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
			$screenshot = LD_BASE_URL . $this->path . '/wp-content' . $theme['Stylesheet Dir'] . '/' . $theme['Screenshot'];
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

		if (!file_exists($this->tmpFolder . '/tables')) {
			mkdir($this->tmpFolder . '/tables', 0777, true);
		}

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

		$this->_copy($this->tmpFolder . '/uploads', $this->absolutePath . '/wp-content/uploads');

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

		update_option('siteurl', LD_BASE_URL . $this->instance['path']);
		update_option('home', LD_BASE_URL . $this->instance['path']);

		wp_cache_flush();

		$this->_unlink($this->tmpFolder);
	}
	
	public function setTheme($theme)
	{
		$this->load_wp();
		switch_theme($theme, $theme);
	}

	public function uninstall()
	{
		$this->load_wp();

		foreach ($this->wpdb->tables as $table) {
			$tablename = $this->wpdb->$table;
			$result = $this->wpdb->query("DROP TABLE $tablename;");
		}

		parent::uninstall();
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

	public function setConfiguration($configuration)
	{
		$this->load_wp();
		foreach ($this->getPreferences('configuration') as $preference) {
			$preference = $preference->toArray();
			$option = $preference['name'];
			$value = isset($configuration[$option]) ? $configuration[$option] : null;
			update_option($option, $value);
		}
		return $this->getConfiguration();
	}

	function load_wp()
	{
		if (empty($this->loaded)) {

			spl_autoload_unregister(array('Zend_Loader', 'autoload'));

			define('WP_INSTALLING', true);
	
			global $wpdb, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem, $is_apache;

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

	function populate_database($user_id)
	{
		$wpdb = $this->wpdb;

		// Default category
		$cat_name = $wpdb->escape(__('Uncategorized'));
		$cat_slug = sanitize_title(_c('Uncategorized|Default category slug'));
		$wpdb->query("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES ('$cat_name', '$cat_slug', '0')");
		$wpdb->query("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ('1', 'category', '', '0', '1')");
		// Default link category
		$cat_name = $wpdb->escape(__('Blogroll'));
		$cat_slug = sanitize_title(_c('Blogroll|Default link category slug'));
		$wpdb->query("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES ('$cat_name', '$cat_slug', '0')");
		$wpdb->query("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ('2', 'link_category', '', '0', '7')");

		// First post
		$now = date('Y-m-d H:i:s');
		$now_gmt = gmdate('Y-m-d H:i:s');
		$first_post_guid = get_option('home') . '/?p=1';
		$wpdb->query("INSERT INTO $wpdb->posts (post_author, post_date, post_date_gmt, post_content, post_excerpt, post_title, post_category, post_name, post_modified, post_modified_gmt, guid, comment_count, to_ping, pinged, post_content_filtered) VALUES ($user_id, '$now', '$now_gmt', '".$wpdb->escape(__('Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!'))."', '', '".$wpdb->escape(__('Hello world!'))."', '0', '".$wpdb->escape(_c('hello-world|Default post slug'))."', '$now', '$now_gmt', '$first_post_guid', '1', '', '', '')");
		$wpdb->query( "INSERT INTO $wpdb->term_relationships (`object_id`, `term_taxonomy_id`) VALUES (1, 1)" );
	}

}
