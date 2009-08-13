<?php

require_once(dirname(__FILE__) . '/prepend.php');

if (file_exists(dirname(__FILE__) . '/default.inc.php')) {
    require(dirname(__FILE__) . '/default.inc.php');
}

$rcmail_config['plugins'] = array('ld');

$rcmail_config['temp_dir'] = LD_TMP_DIR;

$configuration = Zend_Registry::get('instance')->getInstaller()->getConfiguration();
foreach ($configuration as $key => $value) {
    $rcmail_config[$key] = $value;
}
