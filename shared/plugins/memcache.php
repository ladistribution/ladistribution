<?php

class Ld_Plugin_Memcache
{

    public function infos()
    {
        return array(
            'name' => 'Memcache',
            'url' => 'http://ladistribution.net/wiki/plugins/#memcache',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.3',
            'description' => Ld_Translate::translate('Cache data with Memcache to enhance performances.'),
            'license' => 'MIT / GPL'
        );
    }

    const STATUS_OK = 1;
    const STATUS_ERROR = 0;

    public function status()
    {
        if (!class_exists('Memcache')) {
            return array(self::STATUS_ERROR, Ld_Translate::translate('Memcache PHP extension is needed to run this plugin.'));
        }
        try {
            $memcache = new Memcache();
            foreach ($this->servers() as $server) {
                list($host, $port) = $server;
                $memcache->addServer($host, $port);
            }
            $result = $memcache->set('ld-test', 1);
        } catch (Exception $e) {
            $result = false;
        }
        if (empty($result)) {
            return array(self::STATUS_ERROR, Ld_Translate::translate("Can't connect to Memcache servers."));
        }
        return array(self::STATUS_OK, sprintf(Ld_Translate::translate('%s is configured and running.'), 'Memcache'));
    }

    public function load()
    {
        defined('LD_MEMCACHED') OR define('LD_MEMCACHED', class_exists('Memcache'));

        if (constant('LD_MEMCACHED') && class_exists('Memcache')) {
            Ld_Plugin::addAction('Weave:prepend', array($this, 'weave_prepend'));
            Ld_Plugin::addAction('Statusnet:config', array($this, 'statusnet_config'));
            Ld_Plugin::addAction('Wordpress:prepend', array($this, 'wordpress_prepend'));
        }
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'type' => 'textarea', 'label' => Ld_Translate::translate('Memcache servers'),
            'name' => 'memcached_servers', 'defaultValue' => '127.0.0.1:11211'
        );
        return $preferences;
    }

    public function servers()
    {
        $config = Zend_Registry::get('site')->getConfig('memcached_servers');
        if (empty($config)) {
            $config = '127.0.0.1:11211';
        }
        $servers = array();
        $servers_config = explode("\n", $config);
        foreach ($servers_config as $server) {
            list($node, $port) = explode(':', trim($server));
            $port = empty($port) ? 11211 : intval($port);
            if (!empty($node)) {
                $servers[] = array($node, $port);
            }
        }
        return $servers;
    }

    public function wordpress_prepend()
    {
        $servers = $this->servers();
        if (empty($servers)) {
            return;
        }

        global $memcached_servers;
        $memcached_servers = array();
        foreach ($servers as $server) {
            list($host, $port) = $server;
            $memcached_servers[] = "$host:$port";
        }
    }

    public function weave_prepend()
    {
        $servers = $this->servers();
        if (empty($servers)) {
            return;
        }
        list($host, $port) = $servers[0];
        if (!defined('WEAVE_STORAGE_MEMCACHE_HOST')) { define('WEAVE_STORAGE_MEMCACHE_HOST', $host); }
        if (!defined('WEAVE_STORAGE_MEMCACHE_PORT')) { define('WEAVE_STORAGE_MEMCACHE_PORT', $port); }
        if (!defined('WEAVE_STORAGE_MEMCACHE_DECAY')) { define('WEAVE_STORAGE_MEMCACHE_DECAY', 86400); }
    }

    public function statusnet_config()
    {
        $servers = $this->servers();
        if (empty($servers)) {
            return;
        }
        $config = array('servers' => array());
        foreach ($servers as $server) {
            list($host, $port) = $server;
            $config['servers'][] = "$host;$port";
        }
        addPlugin('Memcache', $config);
    }

}
