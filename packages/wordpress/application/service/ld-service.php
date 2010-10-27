<?php

error_reporting(0);

define('LD_DEBUG', false);
define('WP_INSTALLING', true);

require_once( 'wp-load.php' );
require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
require_once( ABSPATH . '/wp-includes/theme.php' );

require_once( ABSPATH . '/dist/service.php' );

function out($status, $message)
{
    header("HTTP/1.0 $status");
    header("Content-Type:text/plain");
    print($message);
    exit(0);
}

function unauthorized($message)
{
    out("401 Unauthorized", $message);
}

function bad($message)
{
    out("400 Bad Request", $message);
}

if ( !Ld_Auth::isAuthenticated() ) {
    unauthorized('Not authenticated');
}

$site = Zend_Registry::get('site');
$admin = $site->getAdmin();
$role = $admin->getUserRole();

if ( $role != 'admin' ) {
    unauthorized('Not admin');
}

if (isset($_GET['method'])) {
    $method = $_GET['method'];
    $params = array();
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $params = Zend_Json::decode($input);
    }
    if (method_exists('Ld_Service_Wordpress', $method)) {
        $result = call_user_func(array('Ld_Service_Wordpress', $method), $params);
        header("Content-Type:application/json");
        echo Zend_Json::encode($result);
    } else {
        bad('Unknown method');
    }
}
