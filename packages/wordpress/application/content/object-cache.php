<?php

if (defined('LD_MEMCACHED') && constant('LD_MEMCACHED') && class_exists('Memcache')) {
    include dirname(__FILE__) . '/object-cache-memcached.php';
} else {
    include dirname(__FILE__) . '/../wp-includes/cache.php';
    $_wp_using_ext_object_cache = false;
}
