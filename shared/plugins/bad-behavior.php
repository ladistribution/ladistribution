<?php

class Ld_Plugin_BadBehavior
{

    public static function infos()
    {
        return array(
            'name' => 'Bad Behavior',
            'url' => 'http://ladistribution.net/wiki/plugins/#bad-behavior',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.1',
            'description' => 'Ban clients with bad bad bad behavior.',
            'license' => 'MIT / GPL'
        );
    }

    public static function load()
    {
        Ld_Plugin::addAction('Admin:prepend', array('Ld_Plugin_BadBehavior', 'generic_prepend'));
        Ld_Plugin::addAction('Wordpress:prepend', array('Ld_Plugin_BadBehavior', 'generic_prepend'));
        Ld_Plugin::addAction('Dokuwiki:prepend', array('Ld_Plugin_BadBehavior', 'generic_prepend'));
        Ld_Plugin::addAction('Bbpress:prepend', array('Ld_Plugin_BadBehavior', 'generic_prepend'));
        Ld_Plugin::addAction('Statusnet:prepend', array('Ld_Plugin_BadBehavior', 'generic_prepend'));
    }

    public static function generic_prepend()
    {
        self::start();
    }

    public static function start()
    {
        if (Ld_Auth::isAuthenticated()) {
            return;
        }

        if (!file_exists(LD_LIB_DIR . "/bad-behavior/version.inc.php")) {
            return;
        }

        define('BB2_CWD', LD_LIB_DIR);

        require_once(LD_LIB_DIR . "/bad-behavior/version.inc.php");
        require_once(LD_LIB_DIR . "/bad-behavior/core.inc.php");

        $options = array(
            'log_table'     => 'badbehaviour',
            'display_stats' => true,
            'strict'        => true,
            'verbose'       => true,
            'logging'       => true,
            'skipblackhole' => false,
            'httpbl_key' => '',
            'httpbl_threat' => '25',
            'httpbl_maxage' => '30',
            'offsite_forms' => true
        );

        bb2_start($options);
    }
    
    public static function log($settings, $package, $key)
    {
        $data = array();
        $data[] = "Bad Behavior: $key";
        $data[] = $package['request_method'];
        $data[] = $package['request_uri'];
        $data[] = $package['server_protocol'];
        $data[] = $package['user_agent'];

        error_log( join(" - ", $data) );
    }

}

// Global Bad Behavior functions

function bb2_relative_path() {  return Zend_Registry::get('site')->getPath(); }
function bb2_db_date() { return gmdate('Y-m-d H:i:s'); }
function bb2_db_affected_rows() { return false; }
function bb2_db_escape($string) { return $string; }
function bb2_db_num_rows($result) { return ($result === FALSE) ? 0 : count($result); }
function bb2_db_query($query) { return false; }
function bb2_db_rows($result) { return $result; }
function bb2_email() { return "badbots@ioerror.us"; }
function bb2_insert($settings, $package, $key) { return false; }
function bb2_banned_callback($settings, $package, $key) { Ld_Plugin_BadBehavior::log($settings, $package, $key); }
