<?php

class Ld_Plugin_Memcache
{

    public static function infos()
    {
        return array(
            'name' => 'Memcache',
            'url' => 'http://ladistribution.net/wiki/plugins/#memcache',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.1',
            'description' => 'Integrate Memcache caching backend.',
            'license' => 'MIT / GPL'
        );
    }

    public static function load()
    {
        defined('LD_MEMCACHED') OR define('LD_MEMCACHED', class_exists('Memcache'));

        if (constant('LD_MEMCACHED') && class_exists('Memcache')) {
            Ld_Plugin::addAction('Weave:prepend', array('Ld_Plugin_Memcache', 'weave_prepend'));
            Ld_Plugin::addAction('Statusnet:config', array('Ld_Plugin_Memcache', 'statusnet_config'));
        }
    }

    public static function weave_prepend()
    {
        $ld_memcached_servers_config = Zend_Registry::get('site')->getConfig('memcached_servers');
        if (empty($ld_memcached_servers_config)) {
            $ld_memcached_servers_config = '127.0.0.1';
        }
        if (!defined('WEAVE_STORAGE_MEMCACHE_HOST')) { define('WEAVE_STORAGE_MEMCACHE_HOST', 'localhost'); }
        if (!defined('WEAVE_STORAGE_MEMCACHE_PORT')) { define('WEAVE_STORAGE_MEMCACHE_PORT', '11211'); }
        if (!defined('WEAVE_STORAGE_MEMCACHE_DECAY')) { define('WEAVE_STORAGE_MEMCACHE_DECAY', 86400); }
    }

    public static function statusnet_config()
    {
        require_once('plugins/MemcachePlugin.php');
        $memcache = new MemcachePlugin();
        // require_once('plugins/LdMemcachePlugin.php');
        // $memcache = new LdMemcachePlugin();
        // addPlugin('Memcache');
        // addPlugin('Memcache', array('servers' => $config['memcached']['server']));
        // $config['memcached']['enabled'] = false;
        // $config['memcached']['server'] = 'localhost';
        // $config['memcached']['port'] = 11211;
    }

}
