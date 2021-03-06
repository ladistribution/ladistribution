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
            'version' => '0.6.42',
            'description' => Ld_Translate::translate('Customise configuration, enable debugging, new features...'),
            'license' => 'MIT / GPL'
        );
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'enable_debugging', 'label' => ('Enable debugging'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'error_reporting', 'label' => ('Error Reporting'),
            'type' => 'text', 'defaultValue' => 'E_ALL'
        );
        $preferences[] = array(
            'name' => 'memory_limit', 'label' => ('Memory Limit'),
            'type' => 'text', 'defaultValue' => '128M'
        );
        $preferences[] = array(
            'name' => 'time_limit', 'label' => ('Time Limit'),
            'type' => 'text', 'defaultValue' => '30'
        );
        $preferences[] = array(
            'name' => 'cache_lifetime', 'label' => ('Cache Lifetime'),
            'type' => 'text', 'defaultValue' => '300'
        );
        $preferences[] = array(
            'name' => 'no_rewrite', 'label' => ('URL Rewriting not available'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'nocompress_js_css', 'label' => ('Use non-compressed JS & CSS'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
      $preferences[] = array(
          'name' => 'disable_appearance', 'label' => ('Disable Appearance'),
          'type' => 'boolean', 'defaultValue' => '0'
      );
        $preferences[] = array(
            'name' => 'active_multi_sites', 'label' => ('Activate Multi Sites'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'active_multi_domains', 'label' => ('Activate Multi Domains'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'active_news_feed', 'label' => ('Activate News Feed'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'active_services', 'label' => ('Activate Services'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        return $preferences;
    }

    public function load()
    {
        $site = Zend_Registry::get('site');
        $enable_debugging = $site->getConfig('enable_debugging');
        if ($enable_debugging) {
            defined('LD_DEBUG') OR define('LD_DEBUG', true);
        }
        $error_reporting = $site->getConfig('error_reporting');
        if ($error_reporting) {
            eval("\$error_reporting = $error_reporting;");
            error_reporting($error_reporting);
        }
        $memory_limit = $site->getConfig('memory_limit');
        if ($memory_limit) {
            ini_set("memory_limit", $memory_limit);
        }
        $time_limit = $site->getConfig('time_limit');
        if ($time_limit) {
            set_time_limit($time_limit);
        }
        $no_rewrite = $site->getConfig('no_rewrite');
        if ($no_rewrite) {
            defined('LD_REWRITE') OR define('LD_REWRITE', false);
        }
        $nocompress_js_css = $site->getConfig('nocompress_js_css');
        if ($nocompress_js_css) {
            defined('LD_COMPRESS_JS') OR define('LD_COMPRESS_JS', false);
            defined('LD_COMPRESS_CSS') OR define('LD_COMPRESS_CSS', false);
        }
        $disable_appearance = $site->getConfig('disable_appearance');
        if ($disable_appearance) {
            defined('LD_APPEARANCE') OR define('LD_APPEARANCE', false);
        }
        $active_multi_sites = $site->getConfig('active_multi_sites');
        if ($active_multi_sites) {
            defined('LD_MULTI_SITES') OR define('LD_MULTI_SITES', true);
        }
        $active_multi_domains = $site->getConfig('active_multi_domains');
        if ($active_multi_domains) {
            defined('LD_MULTI_DOMAINS') OR define('LD_MULTI_DOMAINS', true);
        }
        $active_news_feed = $site->getConfig('active_news_feed');
        if ($active_news_feed) {
            defined('LD_NEWS_FEED') OR define('LD_NEWS_FEED', true);
        }
        $active_services = $site->getConfig('active_services');
        if ($active_services) {
            defined('LD_SERVICES') OR define('LD_SERVICES', true);
        }
    }

}
