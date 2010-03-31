<?php

function ld_akismet_settings($preferences)
{
    $preferences[] = array(
        'name' => 'akismet_api_key', 'type' => 'text', 'label' => 'Akismet API key'
    );
    return $preferences;
}

Ld_Plugin::addFilter('Slotter:preferences', 'ld_akismet_settings');

function ld_akismet_wordpress_prepend()
{
    $site = Zend_Registry::get('site');
    $akismet_api_key = $site->getConfig('akismet_api_key');
    if (!empty($akismet_api_key)) {
        define('WPCOM_API_KEY', $akismet_api_key);
    }
}

Ld_Plugin::addAction('Wordpress:prepend', 'ld_akismet_wordpress_prepend');
