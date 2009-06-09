<?php

$dir = dirname(__FILE__);

require_once($dir . '/dist/config.php');

require_once($dir . '/Bootstrap.php');

Bootstrap::run($dir);
