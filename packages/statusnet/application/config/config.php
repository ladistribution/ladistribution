<?php

require_once(dirname(__FILE__) . "/dist/prepend.php");

$config["site"]["name"] = $configuration['title'];

$config['site']['server'] = $site->getHost();
$config['site']['path'] = substr($site->getPath() . '/' . $application->getPath(), 1);

$config['db']['type'] = 'mysql';
$config["db"]["database"] = sprintf("mysqli://%s:%s@%s/%s", $db["user"], $db["password"], $db["host"], $db["name"]);
$config["db"]["table_prefix"] = $application->getDbPrefix();

$config["cache"]["base"] = substr($application->getDbPrefix(), 0, -1);

if (defined('LD_REWRITE') && constant('LD_REWRITE')) {
	$config["site"]["fancy"] = true;
}

if (defined('LD_DEBUG') && constant('LD_DEBUG')) {
	$config["site"]["logdebug"] = true;
}

$languages = get_all_languages();
$languages['en']['lang'] = 'en_US';
$languages['fr-fr']['lang'] = 'fr_FR';

$locales = $application->getInstaller()->getLocales();
foreach ($languages as $id => $language) {
	$lang = $language['lang'];
	if ($id != 'en' && !in_array($lang, $locales)) {
		unset($languages[$id]);
	}
}

$config['site']['languages'] = $languages;

$locale = $application->getLocale();
if ($locale == 'auto' && isset($_COOKIE['ld-lang'])) {
	$locale = $_COOKIE['ld-lang'];
}

if (isset($locale) && $locale != 'auto') {
	$config['site']['language'] = $locale;
	$config['site']['langdetect'] = false;
}

$config['location']['share'] = 'never';
$config['attachments']['uploads'] = false;
$config['invite']['enabled'] = false;
$config['sms']['enabled'] = false;
$config['emailpost']['enabled'] = false;
$config['site']['closed'] = true;

if (isset($configuration['private']) && $configuration['private']) {
	$config['site']['private'] = true;
}

$config['site']['theme'] = isset($configuration['theme']) ? $configuration['theme'] : 'ld';

unset($config['plugins']['default']['Mapstraction']);
unset($config['plugins']['default']['OpenID']);
unset($config['plugins']['default']['OStatus']);
unset($config['plugins']['default']['Geonames']);
unset($config['plugins']['default']['WikiHashtags']);
unset($config['plugins']['default']['RSSCloud']);

unset($config['plugins']['default']['LilUrl']);
unset($config['plugins']['default']['PtitUrl']);
unset($config['plugins']['default']['SimpleUrl']);
unset($config['plugins']['default']['TightUrl']);

addPlugin('Ld');
addPlugin('LdAuthentication');

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Statusnet:config');
}
