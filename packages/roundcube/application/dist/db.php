<?php

require_once(dirname(__FILE__) . '/prepend.php');

$databases = Ld_Registry::get('site')->getDatabases();
$db = $databases[ Ld_Registry::get('instance')->getDb() ];
$prefix = Ld_Registry::get('instance')->getDbPrefix();

$rcmail_config = array();
$rcmail_config['db_dsnw'] = sprintf('mysql://%s:%s@%s/%s', $db['user'], $db['password'], $db['host'], $db['name']);
$rcmail_config['db_dsnr'] = '';
$rcmail_config['db_max_length'] = 512000;
$rcmail_config['db_persistent'] = FALSE;
$rcmail_config['db_table_users'] = $prefix . 'users';
$rcmail_config['db_table_identities'] = $prefix . 'identities';
$rcmail_config['db_table_contacts'] = $prefix . 'contacts';
$rcmail_config['db_table_session'] = $prefix . 'session';
$rcmail_config['db_table_cache'] = $prefix . 'cache';
$rcmail_config['db_table_messages'] = $prefix . 'messages';
