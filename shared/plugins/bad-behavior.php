<?php

class Ld_Plugin_BadBehavior
{

    public function infos()
    {
        return array(
            'name' => 'Bad Behavior',
            'url' => 'http://ladistribution.net/wiki/plugins/#bad-behavior',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.91',
            'description' => Ld_Translate::translate('Detects and automatically blocks suspicious accesses to site.'),
            'license' => 'MIT / GPL'
        );
    }

    public function status()
    {
        $httpbl_key = Zend_Registry::get('site')->getConfig('bb_httpbl_key', '');
        if (empty($httpbl_key)) {
            return array(1, sprintf(Ld_Translate::translate('%s is running.') . ' ' . Ld_Translate::translate('Http:BL configuration is optional.'), 'Bad Behavior'));
        }
        return array(1, sprintf(Ld_Translate::translate('%s is running.') . ' ' . Ld_Translate::translate('Http:BL is configured.'), 'Bad Behavior'));
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'bb_httpbl_key', 'label' => Ld_Translate::translate('Http:BL API Key'),
            'type' => 'text', 'defaultValue' => ''
        );
        return $preferences;
    }

    public function load()
    {
        Ld_Plugin::addAction('Admin:prepend', array($this, 'generic_prepend'), 5);
        Ld_Plugin::addAction('Wordpress:prepend', array($this, 'generic_prepend'), 5);
        Ld_Plugin::addAction('Dokuwiki:prepend', array($this, 'generic_prepend'), 5);
        Ld_Plugin::addAction('Bbpress:prepend', array($this, 'generic_prepend'), 5);
        Ld_Plugin::addAction('Statusnet:prepend', array($this, 'generic_prepend'), 5);
    }

    public function generic_prepend()
    {
        $this->start();
    }

    public function start()
    {
        if (Ld_Auth::isAuthenticated()) {
            return;
        }

        if (!Ld_Files::exists(LD_LIB_DIR . "/bad-behavior/version.inc.php")) {
            return;
        }

        define('BB2_CWD', LD_LIB_DIR);

        require_once(LD_LIB_DIR . "/bad-behavior/core.inc.php");

        $httpbl_key = Zend_Registry::get('site')->getConfig('httpbl_key', '');

        $options = array(
            'log_table'     => 'badbehaviour',
            'display_stats' => false,
            'strict'        => false,
            'verbose'       => false,
            'logging'       => false,
            'httpbl_key'    => $httpbl_key,
            'httpbl_threat' => '25',
            'httpbl_maxage' => '30',
            'offsite_forms' => false,
            'reverse_proxy' => false
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

function bb2_relative_path() { return Zend_Registry::get('site')->getPath(); }
function bb2_db_date() { return gmdate('Y-m-d H:i:s'); }
function bb2_db_affected_rows() { return false; }
function bb2_db_escape($string) { return $string; }
function bb2_db_num_rows($result) { return ($result === FALSE) ? 0 : count($result); }
function bb2_db_query($query) { return false; }
function bb2_db_rows($result) { return $result; }
function bb2_email() { return "spam@ladistribution.net"; }
function bb2_insert($settings, $package, $key) { return false; }
function bb2_banned_callback($settings, $package, $key) { Ld_Plugin_BadBehavior::log($settings, $package, $key); }
