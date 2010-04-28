<?php

class Ld_Plugin_ThemeUnlocker
{

    public function infos()
    {
        return array(
            'name' => 'Theme Unlocker',
            'url' => 'http://ladistribution.net/wiki/plugins/#theme-unlocker',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.3',
            'description' => Ld_Translate::translate('Allow administrators to install, update, delete any Wordpress theme.'),
            'license' => 'MIT / GPL'
        );
    }

    public function status()
    {
        return array(1, sprintf(Ld_Translate::translate('%s is running.'), 'Theme Unlocker'));
    }
    
    public function load()
    {
        Ld_Plugin::addAction('Wordpress:plugin', array($this, 'wordpress_init'), 20);
    }

    public function wordpress_init()
    {
        add_filter('ld_disable_submenus', array($this, 'wordpress_submenu_filter'));
        add_filter('ld_enable_admin_caps', array($this, 'wordpress_enable_admin_caps'));
        add_filter('ld_disable_admin_caps', array($this, 'wordpress_disable_admin_caps'));
        add_filter('ld_theme_update', array($this, 'wordpress_theme_update'));
    }

    public function wordpress_submenu_filter($submenus)
    {
        $pages = array('themes.php', 'theme-install.php');
        foreach ($pages as $page) {
            $index = array_search($page, $submenus);
            if (isset($index)) {
                unset($submenus[$index]);
            }
        }
        return $submenus;
    }

    public function wordpress_get_enabled_caps()
    {
        return array('install_themes', 'delete_themes', 'update_themes');
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

}
