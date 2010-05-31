<?php

class Ld_Plugin_WordpressUnlocker
{

    public function infos()
    {
        return array(
            'name' => 'WordPress Unlocker',
            'url' => 'http://ladistribution.net/wiki/plugins/#wordpress-unlocker',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.1',
            'description' => Ld_Translate::translate('Unlock WordPress native themes/plugins/users mechanisms.'),
            'license' => 'MIT / GPL'
        );
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'unlock_themes', 'label' => Ld_Translate::translate('Unlock themes'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'unlock_plugins', 'label' => Ld_Translate::translate('Unlock plugins'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        $preferences[] = array(
            'name' => 'unlock_users', 'label' => Ld_Translate::translate('Unlock users'),
            'type' => 'boolean', 'defaultValue' => '0'
        );
        return $preferences;
    }

    public function status()
    {
        return array(1, sprintf(Ld_Translate::translate('%s is running.'), 'WordPress Unlocker'));
    }
    
    public function load()
    {
        Ld_Plugin::addAction('Wordpress:plugin', array($this, 'wordpress_init'), 20);
    }

    public function wordpress_init()
    {
        add_filter('ld_disable_menus', array($this, 'wordpress_menu_filter'));
        add_filter('ld_disable_submenus', array($this, 'wordpress_submenu_filter'));
        add_filter('ld_enable_admin_caps', array($this, 'wordpress_enable_admin_caps'));
        add_filter('ld_disable_admin_caps', array($this, 'wordpress_disable_admin_caps'));
        $unlock_themes = Zend_Registry::get('site')->getConfig('unlock_themes');
        if ($unlock_themes) {
            add_filter('ld_theme_update', array($this, 'wordpress_theme_update'));
        }
        $unlock_plugins = Zend_Registry::get('site')->getConfig('unlock_plugins');
        if ($unlock_plugins) {
            add_filter('ld_plugin_update', array($this, 'wordpress_plugin_update'));
        }
    }

    public function wordpress_menu_filter($submenus)
    {
        $pages = array();
        $unlock_plugins = Zend_Registry::get('site')->getConfig('unlock_plugins');
        if ($unlock_plugins) {
            $pages[] = 'plugins.php';
        }
        $unlock_users = Zend_Registry::get('site')->getConfig('unlock_users');
        if ($unlock_users) {
            $pages[] = 'users.php';
        }
        foreach ($pages as $page) {
            $index = array_search($page, $submenus);
            if (isset($index)) {
                unset($submenus[$index]);
            }
        }
        return $submenus;
    }

    public function wordpress_submenu_filter($submenus)
    {
        $pages = array();
        $unlock_themes = Zend_Registry::get('site')->getConfig('unlock_themes');
        if ($unlock_themes) {
            $pages[] = 'themes.php';
            $pages[] = 'theme-install.php';
        }
        foreach ($pages as $page) {
            $index = array_search($page, $submenus);
            if (isset($index)) {
                unset($submenus[$index]);
            }
        }
        return $submenus;
    }

    public function wordpress_get_themes_caps()
    {
        return array('install_themes', 'delete_themes', 'update_themes');
    }

    public function wordpress_get_plugins_caps()
    {
        return array('install_plugins', 'delete_plugins', 'update_plugins');
    }

    public function wordpress_get_users_caps()
    {
        return array('create_users', 'edit_users', 'delete_users');
    }

    public function wordpress_get_enabled_caps()
    {
        $caps = array();
        $unlock_themes = Zend_Registry::get('site')->getConfig('unlock_themes');
        if ($unlock_themes) {
            $caps = array_unique(array_merge($caps, $this->wordpress_get_themes_caps()));
        }
        $unlock_plugins = Zend_Registry::get('site')->getConfig('unlock_plugins');
        if ($unlock_plugins) {
            $caps = array_unique(array_merge($caps, $this->wordpress_get_plugins_caps()));
        }
        $unlock_users = Zend_Registry::get('site')->getConfig('unlock_users');
        if ($unlock_users) {
            $caps = array_unique(array_merge($caps, $this->wordpress_get_users_caps()));
        }
        return $caps;
    }

    public function wordpress_enable_admin_caps($caps)
    {
        $caps = array_unique(array_merge($caps, $this->wordpress_get_enabled_caps()));
        return $caps;
    }

    public function wordpress_disable_admin_caps($caps)
    {
        foreach ($this->wordpress_get_enabled_caps() as $cap) {
            $index = array_search($cap, $caps);
            if (isset($index)) {
                unset($caps[$index]);
            }
        }
        return $caps;
    }

    public static function wordpress_theme_update($bool = false)
    {
        return true;
    }

    public static function wordpress_plugin_update($bool = false)
    {
        return true;
    }

}
