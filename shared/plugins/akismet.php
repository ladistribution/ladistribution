<?php

class Ld_Plugin_Akismet
{

    public function infos()
    {
        return array(
            'name' => 'Akismet',
            'url' => 'http://ladistribution.net/wiki/plugins/#akismet',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.3',
            'description' => Ld_Translate::translate('Checks user submitted content with Akismet to see if it look like spam.'),
            'license' => 'MIT / GPL'
        );
    }

    const STATUS_OK = 1;
    const STATUS_ERROR = 0;

    public function status()
    {
        if ($api_key = $this->get_api_key()) {
            return array(self::STATUS_OK, sprintf(Ld_Translate::translate('%s is configured and running.'), 'Akismet'));
        }
        return array(self::STATUS_ERROR, sprintf(Ld_Translate::translate('%s is not running. Check your configuration to enable it.'), 'Akismet'));
    }

    public function preferences()
    {
        $preference = array(
            'name' => 'akismet_api_key', 'type' => 'text', 'label' => Ld_Translate::translate('Akismet API key')
        );
        return array($preference);
    }

    public function load()
    {
        Ld_Plugin::addAction('Wordpress:prepend', array($this, 'wordpress_prepend'));
        Ld_Plugin::addAction('Bbpress:plugin', array($this, 'bbpress_plugin'));
    }

    public function get_api_key()
    {
        $site = Zend_Registry::get('site');
        $api_key = $site->getConfig('akismet_api_key');
        if (empty($api_key)) {
            return null;
        }
        return $api_key;
    }

    public function wordpress_prepend()
    {
        if ($api_key = $this->get_api_key()) {
            define('WPCOM_API_KEY', $api_key);
        }
    }

    public function bbpress_plugin()
    {
        add_filter('bb_get_option_akismet_key', array($this, 'bbpress_option_akismet_key'));
    }

    public function bbpress_option_akismet_key($value)
    {
        if ($api_key = $this->get_api_key()) {
            return $api_key;
        }
        return $value;
    }

}
