<?php

if (defined('LD_MEMCACHED') && constant('LD_MEMCACHED') && class_exists('Memcache')) {
    include dirname(__FILE__) . '/object-cache-memcached.php';
    $ld_memcached_servers_config = Zend_Registry::get('site')->getConfig('memcached_servers');
    if (empty($ld_memcached_servers_config)) {
        $ld_memcached_servers_config = '127.0.0.1';
    }
    $memcached_servers = explode(';', $ld_memcached_servers_config);
} else {
    include dirname(__FILE__) . '/../wp-includes/cache.php';
    $_wp_using_ext_object_cache = false;
}
