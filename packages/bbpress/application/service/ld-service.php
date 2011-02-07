<?php

define('LD_DEBUG', false);

require_once( 'dist/config.php' );

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

if (isset($_GET['method'])) {

    $method = $_GET['method'];

    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $params = Zend_Json::decode($input);
    } else {
        $params = array();
    }

    if ($method == 'init') {
        define('BB_INSTALLING', true);
    }

    ob_start();

    require_once( 'bb-load.php');
    require_once( BB_PATH . '/bb-admin/includes/functions.bb-plugin.php');
    require_once( BB_PATH . '/dist/service.php' );

    ob_end_clean();

    error_reporting(0);

    $infos = $application->getInfos();
    if (empty($_COOKIE['ld-secret']) || $_COOKIE['ld-secret'] != $infos['secret']) {
        if ( !Ld_Auth::isAuthenticated() ) {
            unauthorized('Not authenticated');
        }
        $admin = $site->getAdmin();
        $role = $admin->getUserRole();
        if ( $role != 'admin' ) {
            unauthorized('Not admin');
        }
    }

    if (method_exists('Ld_Service_Bbpress', $method)) {
        $result = call_user_func(array('Ld_Service_Bbpress', $method), $params);
        header("Content-Type:application/json");
        echo Zend_Json::encode($result);
    } else {
        bad('Unknown method');
    }
}
