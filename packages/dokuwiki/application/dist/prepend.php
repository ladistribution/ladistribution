<?php

if (file_exists(DOKU_INC . 'dist/config.php')) {
	require_once DOKU_INC . 'dist/config.php';
}

$site = Zend_Registry::get('site');

$application = $site->getInstance(DOKU_INC);
Zend_Registry::set('application', $application);

$locale = $application->getLocale();

if ($locale == 'auto') {
	if (isset($_COOKIE['ld-lang'])) {
		$locale = $_COOKIE['ld-lang'];
	}
}

if (isset($locale) && $locale != 'auto') {
	$conf['lang'] = substr($locale, 0, 2);
}

if ($site->getConfig('open_registration', 0) == 0) {
	$disableactions = explode(',', $conf['disableactions']);
	$disableactions[] = 'register';
	$conf['disableactions'] = implode(',', array_unique($disableactions));
}

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Dokuwiki:prepend');
}
