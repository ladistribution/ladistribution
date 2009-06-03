<?php
require_once dirname(__FILE__) . '/../dist/prepend.php';
if (!defined("_ECRIRE_INC_VERSION")) return;
define('_MYSQL_SET_SQL_MODE',true);
$GLOBALS['spip_connect_version'] = 0.7;
spip_connect_db($db['host'],'', $db['user'], $db['password'], $db['name'], 'mysql', $dbPrefix, '');
?>