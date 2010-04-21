<?php

$dir = dirname(__FILE__);

require_once($dir . '/dist/config.php');

if (class_exists('Ld_Plugin')) {
	Ld_Plugin::doAction('Admin:prepend');
}

require_once($dir . '/Bootstrap.php');

Bootstrap::run($dir);
