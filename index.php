<?php
ini_set("memory_limit", "128M");
define('LD_ROOT_CONTEXT', true);
if (file_exists('admin/dispatch.php')) require_once('admin/dispatch.php');
else echo 'La Distribution Admin component not installed.';
// $cfgFile = $site->getDirectory('dist') . "/config.json";
// $config = Ld_Files::getJson($cfgFile);
// $config['root_admin'] = false;
// $config['root_application'] = '';
// Ld_Files::putJson($cfgFile, $config);