<?php

class Ld_Installer_Dokuwiki extends Ld_Installer
{

    public $defaultConfiguration = array(
        'title'         => 'My Wiki',
        'license'       => '',
        'useacl'        => 1,
        'superuser'     => '@admin',
        'relnofollow'   => 0,
        'indexdelay'    => 0,
        'updatecheck'   => 0,
        'useslash'      => 1,
        'rss_type'      => 'atom1',
        'rss_linkto'    => 'page',
        'maxtoclevel'   => 0,
        'authtype'      => 'ld'
    );

    public function install($preferences = array())
    {
        // Deploy the files
        parent::install($preferences);

        // Rewrite Rules
        if (constant('LD_REWRITE')) {
            $path = $this->site->getBasePath() . '/' . $this->path . '/';
            $htaccess  = "RewriteEngine on\n";
            $htaccess .= "RewriteBase $path\n";
            $htaccess .= "RewriteRule ^_media/(.*)              lib/exe/fetch.php?media=$1  [QSA,L]\n";
            $htaccess .= "RewriteRule ^_detail/(.*)             lib/exe/detail.php?media=$1  [QSA,L]\n";
            $htaccess .= "RewriteRule ^_export/([^/]+)/(.*)     doku.php?do=export_$1&id=$2  [QSA,L]\n";
            $htaccess .= "RewriteRule ^$                        doku.php [L]\n";
            $htaccess .= "RewriteCond %{REQUEST_FILENAME}       !-f\n";
            $htaccess .= "RewriteCond %{REQUEST_FILENAME}       !-d\n";
            $htaccess .= "RewriteRule (.*)                      doku.php?id=$1  [QSA,L]\n";
            file_put_contents($this->absolutePath . "/.htaccess", $htaccess);
        }

        // Configuration file
        $conf = $this->defaultConfiguration;
        if (constant('LD_REWRITE')) {
            $conf['userewrite'] = 1;
        }
        foreach (array('title', 'template', 'lang') as $key) {
            if (isset($preferences[$key])) {
                $conf[$key] = $preferences[$key];
            }
        }
        $this->setConfiguration($conf);

        // Users
        // $user = array(
        //     $preferences['admin_username'],
        //     md5($preferences['admin_password']),
        //     $preferences['admin_fullname'],
        //     $preferences['admin_email'],
        //     'admin,user'
        // );
        // $cfg_users = join(":", $user) . "\n";
        // file_put_contents($this->absolutePath . "/conf/users.auth.php", $cfg_users);

        // ACL
        $cfg_acl = '';
        if ($preferences['policy'] == 2) {
            $cfg_acl .=  "*               @ALL          0\n";
            $cfg_acl .=  "*               @user         8\n";
        } elseif ($preferences['policy'] == 1) {
            $cfg_acl .=  "*               @ALL          1\n";
            $cfg_acl .=  "*               @user         8\n";
        } else {
            $cfg_acl .=  "*               @ALL          8\n";
        }
        Ld_Files::put($this->absolutePath . "/conf/acl.auth.php", $cfg_acl);
    }

    public function postInstall($preferences = array())
    {
        parent::postInstall($preferences);

        if (!empty($preferences['administrator'])) {
            $username = $preferences['administrator']['username'];
            $this->setUserRoles(array($username => 'admin'));
        }
    }

    public function setConfiguration($configuration, $type = 'general')
    {
        if ($type == 'theme') {
            $template = $this->getCurrentTheme();
            $configuration = array('tpl' => array($template => $configuration));
        }

        $conf = array_merge($this->getConfiguration(), $configuration);
        $cfg_local  = "<?php\n";
        foreach ($conf as $key => $value) {
            if ($key == 'tpl') {
                foreach ($value as $tpl_id => $tpl) {
                    foreach ($tpl as $tpl_key => $tpl_value) {
                        $cfg_local .= '$' . "conf['$key']['$tpl_id']['$tpl_key'] = " . $this->_getValueString($tpl_value) . ";\n";
                    }
                }
            } else {
                $cfg_local .= '$' . "conf['$key'] = " . $this->_getValueString($value) . ";\n";
            }
        }
        Ld_Files::put($this->absolutePath . "/conf/local.php", $cfg_local);

        if (isset($configuration['title'])) {
            $this->instance->setInfos(array('name' => $configuration['title']))->save();
        }

        if ($type == 'theme') {
            return $conf['tpl'][$template];
        }
        return $conf;
    }

    protected function _getValueString($value)
    {
        if (is_int($value)) {
            return $value;
        } else if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            return "'" . addcslashes($value, "'") . "'";
        }
    }

    public function getConfiguration($type = 'general')
    {
        $conf = array();
        if (file_exists($this->absolutePath . "/conf/local.php")) {
            include $this->absolutePath . "/conf/local.php";
        }

        if ($type == 'theme') {
            $template = $this->getCurrentTheme();
            return isset($conf['tpl'][$template]) ? $conf['tpl'][$template] : array();
        }

        return $conf;
    }

    public function setTheme($theme)
    {
        $themes = $this->getThemes();
        if (isset($themes[$theme])) {
            $this->setConfiguration(array('template' => $theme));
        }
        return $theme;
    }

    public function getThemes()
    {
        $dirs = Ld_Files::getDirectories($this->absolutePath . '/lib/tpl/');

        $template = $this->getCurrentTheme();

        $themes = array();
        foreach ($dirs as $id) {
            $themes[$id] = array(
                'name' => $id,
                'active' => ($template == $id)
            );
            if (file_exists($this->absolutePath . '/lib/tpl/' . $id . '/screenshot.png')) {
                $themes[$id]['screenshot'] = $this->instance->getUrl() . 'lib/tpl/' . $id . '/screenshot.png';
            }
        }
        return $themes;
    }

    public function getPreferences($type)
    {
        switch ($type) {
            case 'theme':
                return $this->getThemePreferences();
            default:
                $preferences = parent::getPreferences($type);
                if ($type != 'install') {
                    $lang = $this->_getLangPreference();
                    if (count($lang['options']) > 1) {
                        $preferences[] = $lang;
                    }
                }
                return $preferences;
        }
    }

    public function _getLangPreference()
    {
        $dirs = Ld_Files::getDirectories($this->absolutePath . '/inc/lang/');
        $options = array();
        foreach ($dirs as $lang) {
            if ($lang == 'dist') {
                continue;
            }
            $options[] = array('label' => $lang, 'value' => $lang);
        }
        return array('name' => 'lang', 'label' => 'Lang', 'defaultValue' => 'en', 'type' => 'list', 'options' => $options);
    }

    public function getCurrentTheme()
    {
        $conf  = $this->getConfiguration();
        $template = isset($conf['template']) ? $conf['template'] : 'default';
        return $template;
    }

    public function getThemePreferences()
    {
        $template = $this->getCurrentTheme();
        if ($template != 'default') {
            $template_dir = $this->absolutePath . '/lib/tpl/' . $template;
            if (file_exists($template_dir) && file_exists($template_dir . '/dist/manifest.xml')) {
                $template_installer = new Ld_Installer(array('dir' => $template_dir));
                return $template_installer->getPreferences('configuration');
            }
        }
        return array();
    }

    public $roles = array('admin', 'user');

    public $defaultRole = 'user';

    public function getRoles()
    {
        return $this->roles;
    }

    public function getBackupDirectories()
    {
        return array(
            'data' => $this->absolutePath . '/data/',
            'conf' => $this->absolutePath . '/conf/'
        );
    }

    public function restore($filename, $absolute = false)
    {
        parent::restore($filename, $absolute);

        Ld_Files::unlink($this->absolutePath . '/data');

        Ld_Files::copy($this->tmpFolder . '/data', $this->absolutePath . '/data');
        Ld_Files::copy($this->tmpFolder . '/conf', $this->absolutePath . '/conf');

        Ld_Files::unlink($this->tmpFolder);
    }

}
