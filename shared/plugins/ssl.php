<?php

class Ld_Plugin_Ssl
{

    public function infos()
    {
        return array(
            'name' => 'SSL',
            'url' => 'http://ladistribution.net/wiki/plugins/#ssl',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.3',
            'description' => Ld_Translate::translate('Make SSL policy definable.'),
            'license' => 'MIT / GPL'
        );
    }

    public function load()
    {
        // $this->force_ssl();
        Ld_Plugin::addAction('Statusnet:config', array($this, 'statusnet_config'));
        Ld_Plugin::addAction('Wordpress:prepend', array($this, 'wordpress_prepend'));
        Ld_Plugin::addAction('Wordpress:plugin', array($this, 'wordpress_plugin'));
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'ssl_support', 'label' => Ld_Translate::translate('HTTPS support'),
            'type' => 'list', 'defaultValue' => 'never', 'options' => array(
                array('value' => 'never', 'label' => Ld_Translate::translate('None (not available)')),
                array('value' => 'sometimes', 'label' => Ld_Translate::translate('Sometimes (available, used for sensitive pages)')),
                array('value' => 'always', 'label' => Ld_Translate::translate('Always (available, used everywhere)'))
            )
        );
        return $preferences;
    }

    public function config()
    {
        $site = Zend_Registry::get('site');
        return $site->getConfig('ssl_support', 'never');
    }

    public function force_ssl()
    {
        $ssl_support = $this->config();
        if ($ssl_support == 'always' && (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on")) {
            $newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            header('Location:' . $newurl);
            exit();
        }
    }

    public function statusnet_config()
    {
        $ssl_support = $this->config();
        if (isset($ssl_support)) {
            global $config;
            $config['site']['ssl'] = $ssl_support;
        }
    }

    public function wordpress_prepend()
    {
        $ssl_support = $this->config();
         if (isset($ssl_support) && ($ssl_support == 'sometimes' || $ssl_support == 'always')) {
             define('FORCE_SSL_LOGIN', true);
             define('FORCE_SSL_ADMIN', true);
        }
    }

    public function wordpress_plugin()
    {
        add_action('plugins_loaded', array($this, 'wordpress_force_ssl'));
    }

    /*
     * Inspired by:
     * http://codex.wordpress.org/Administration_Over_SSL#Force_SSL_Plugin
     */
    public function wordpress_force_ssl()
    {
        $ssl_support = $this->config();
        if (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
            $is_login = strpos($_SERVER["REQUEST_URI"], '/wp-login.php') !== false;
            $is_admin = strpos($_SERVER["REQUEST_URI"], '/wp-admin/') !== false;
            if ($ssl_support == 'always' || ($ssl_support == 'sometimes' && $is_admin) || ($ssl_support == 'sometimes' && $is_login)) {
                $newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
                wp_redirect($newurl);
                exit();
            }
        }
    }

}

// function ld_ssl_get_application_url($url, $class)
// {
//     $https_support = $class->getSite()->getConfig('https_support');
//     if (empty($https_support) || $https_support = 'none') {
//         $url = str_replace('http://', 'https://', $url);
//     }
//     return $url;
// }
