<?php

if (file_exists(dirname(__FILE__) . '/config.php')) {
	require_once dirname(__FILE__) . '/config.php';
}

$site = Zend_Registry::get('site');

$application = $site->getInstance( dirname(__FILE__) . '/..' );

$locale = $application->getLocale();

if ($locale == 'auto') {
	if (isset($_COOKIE['ld-lang'])) {
		$locale = $_COOKIE['ld-lang'];
	}
}

if (isset($locale) && $locale != 'auto') {
	$conf['lang'] = substr($locale, 0, 2);
}

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Dokuwiki:prepend');
}

require_once 'Ld/Files.php';
Ld_Files::includes(dirname(__FILE__) . '/prepend/');
