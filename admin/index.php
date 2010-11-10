<?php

$appdir = dirname(__FILE__);

require_once($appdir . '/dist/config.php');

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Admin:prepend');
}

require_once($appdir . '/Bootstrap.php');

Bootstrap::run($appdir);
