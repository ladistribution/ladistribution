<?php

if (file_exists(dirname(__FILE__) . '/config.php')) {
	require_once dirname(__FILE__) . '/config.php';
}

require_once 'Ld/Files.php';
Ld_Files::includes(dirname(__FILE__) . '/prepend/');
