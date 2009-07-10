<?php defined("SYSPATH") or die("No direct script access.");

require_once(dirname(__FILE__) . '/../dist/prepend.php');

$config['default'] = array(
  'benchmark'     => false,
  'persistent'    => false,
  'connection'    => array(
    'type'     => 'mysqli',
    'user'     => $db['user'],
    'pass'     => $db['password'],
    'host'     => $db['host'],
    'port'     => false,
    'socket'   => false,
    'database' => $db['name']
  ),
  'character_set' => 'utf8',
  'table_prefix'  => $application->getDbPrefix(),
  'object'        => true,
  'cache'         => false,
  'escape'        => true
);
