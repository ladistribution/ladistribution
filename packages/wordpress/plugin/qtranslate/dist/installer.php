<?php

class Ld_Installer_Wordpress_Plugin_Qtranslate extends Ld_Installer_Wordpress_Plugin
{

	public $plugin_file = 'qtranslate/qtranslate.php';

	public function install($preferences)
	{
		parent::install($preferences);

		$qtranslate_locales = array();
		foreach ($this->getSite()->getLocales() as $id => $label) {
			$qtranslate_locales[] = substr($id, 0, 2);
		}

		$qtranslate_default_locale = 'en';

		// $locale = $this->getInstance()->getLocale();
		// if ($locale != 'auto') {
		// 	$qtranslate_default_locale = substr($locale, 0, 2);
		// }

		$qtranslate_options = array(
			'qtranslate_enabled_languages' => $qtranslate_locales,
			'qtranslate_default_language' => $qtranslate_default_locale,
			'qtranslate_auto_update_mo' => 0
		);

		foreach ($qtranslate_options as $key => $value) {
			update_option($key, $value);
		}

		// add qtranslate widget to sidebar
	}

}
