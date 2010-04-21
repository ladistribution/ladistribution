<?php

class Ld_Plugin_Akismet
{

    public static function infos()
    {
        return array(
            'name' => 'Akismet',
            'url' => 'http://ladistribution.net/wiki/plugins/#akismet',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.1',
            'description' => 'Filter user submitted content (like comments) using Akismet antispam service.',
            'license' => 'MIT / GPL'
        );
    }

    public static function load()
    {
        Ld_Plugin::addFilter('Slotter:preferences', array('Ld_Plugin_Akismet', 'slotter_preferences'));
        Ld_Plugin::addAction('Wordpress:prepend', array('Ld_Plugin_Akismet', 'wordpress_prepend'));
        Ld_Plugin::addAction('Bbpress:plugin', array('Ld_Plugin_Akismet', 'bbpress_plugin'));
    }

    public static function slotter_preferences($preferences)
    {
        $preferences[] = array(
            'name' => 'akismet_api_key', 'type' => 'text', 'label' => 'Akismet API key'
        );
        return $preferences;
    }

    public static function wordpress_prepend()
    {
        $site = Zend_Registry::get('site');
        $akismet_api_key = $site->getConfig('akismet_api_key');
        if (!empty($akismet_api_key)) {
            define('WPCOM_API_KEY', $akismet_api_key);
        }
    }

    public static function bbpress_plugin()
    {
        add_filter('bb_get_option_akismet_key', array('Ld_Plugin_Akismet', 'bbpress_option_akismet_key'));
    }

    public static function bbpress_option_akismet_key($value)
    {
        $site = Zend_Registry::get('site');
        $akismet_api_key = $site->getConfig('akismet_api_key');
        if (!empty($akismet_api_key)) {
            return $akismet_api_key;
        }
        return $value;
    }

}
