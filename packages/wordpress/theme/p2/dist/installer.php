<?php

class Installer_Wordpress_Theme_P2 extends Ld_Installer
{

	protected $_theme = array(
		'name' => 'P2',
		'template' => 'p2',
		'stylesheet' => 'p2'
	);

	private function load_wp()
	{
		if (empty($this->loaded)) {

			global $wpdb, $wp_version, $wp_rewrite, $wp_db_version, $wp_taxonomies, $wp_filesystem;

			require_once $this->absolutePath . "/../../../wp-load.php";
			require_once $this->absolutePath . "/../../../wp-includes/theme.php";

			$this->loaded = true;

		}
	}

	public function uninstall()
	{
		$this->load_wp();

		$current_theme = get_current_theme();

		if ($current_theme == $this->_theme['name']) {
			switch_theme('default', 'default');
		}

		parent::uninstall();
	}

}
