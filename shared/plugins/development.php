<?php

class Ld_Plugin_Development
{

    public function infos()
    {
        return array(
            'name' => 'Development',
            'url' => 'http://ladistribution.net/wiki/plugins/#development',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.1',
            'description' => Ld_Translate::translate('Customise configuration, enable debugging, new features...'),
            'license' => 'MIT / GPL'
        );
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'enable_debugging', 'label' => Ld_Translate::translate('Enable debugging'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'memory_limit', 'label' => Ld_Translate::translate('Memory Limit'),
            'type' => 'text', 'defaultValue' => '32M'
        );
        $preferences[] = array(
            'name' => 'time_limit', 'label' => Ld_Translate::translate('Time Limit'),
            'type' => 'text', 'defaultValue' => '30'
        );
        $preferences[] = array(
            'name' => 'include_path', 'label' => Ld_Translate::translate('Include Path'),
            'type' => 'text', 'defaultValue' => str_replace(LD_LIB_DIR . PATH_SEPARATOR, '', ini_get('include_path'))
        );
        $preferences[] = array(
            'name' => 'active_ajax_users', 'label' => Ld_Translate::translate('Active ajax style user management'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        return $preferences;
    }

    public function load()
    {
        $enable_debugging = Zend_Registry::get('site')->getConfig('enable_debugging');
        if ($enable_debugging) {
            defined('LD_DEBUG') OR define('LD_DEBUG', true);
        }
        $memory_limit = Zend_Registry::get('site')->getConfig('memory_limit');
        if ($memory_limit) {
            ini_set("memory_limit", $memory_limit);
        }
        $time_limit = Zend_Registry::get('site')->getConfig('time_limit');
        if ($time_limit) {
            set_time_limit($time_limit);
        }
        $include_path = Zend_Registry::get('site')->getConfig('include_path');
        if ($include_path) {
            $path = empty($include_path) ? LD_LIB_DIR : LD_LIB_DIR . PATH_SEPARATOR . $include_path;
            set_include_path($path);
        }
        $active_ajax_users = Zend_Registry::get('site')->getConfig('active_ajax_users');
        if ($active_ajax_users) {
            defined('LD_AJAX_USERS') OR define('LD_AJAX_USERS', true);
        }
    }

}
