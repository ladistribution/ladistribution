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
        'authtype'      => 'ld',
        'autopasswd'    => 0, // autogenerate passwords ?
        'breadcrumbs'   => 0, // hide bread crumbs
    );

    public function install($preferences = array())
    {
        parent::install($preferences);

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
        if (isset($conf['lang'])) {
            if ($conf['lang'] != 'auto') {
                $conf['lang'] = substr($conf['lang'], 0, 2);
            }
        }
        $conf['basedir'] = $this->getSite()->getBasePath() . '/' . $this->getPath() . '/';
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
        if (isset($preferences['policy']) && $preferences['policy'] == 2) {
            $cfg_acl .=  "*               @ALL          0\n";
            $cfg_acl .=  "*               @user         8\n";
        } elseif (isset($preferences['policy']) && $preferences['policy'] == 1) {
            $cfg_acl .=  "*               @ALL          1\n";
            $cfg_acl .=  "*               @user         8\n";
        } else {
            $cfg_acl .=  "*               @ALL          8\n";
        }
        Ld_Files::put($this->getAbsolutePath() . "/conf/acl.auth.php", $cfg_acl);
    }

    public function postInstall($preferences = array())
    {
        parent::postInstall($preferences);

        if (isset($preferences['lang'])) {
            $this->getInstance()->setInfos(array('locale' => $preferences['lang']))->save();
        }

        if (!empty($preferences['administrator'])) {
            $username = $preferences['administrator']['username'];
            $this->setUserRoles(array($username => 'admin'));
        }

        Ld_Files::unlink($this->getAbsolutePath() . '/lib/tpl/minimal/dist');

        $this->handleRewrite();
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
            if ($key == 'lang' && $value == 'auto') {
                $value = 'en';
            }
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
        Ld_Files::put($this->getAbsolutePath() . "/conf/local.php", $cfg_local);

        if (isset($configuration['name']) && isset($this->instance)) {
            $this->instance->setInfos(array('name' => $configuration['name']))->save();
        }

        if (isset($configuration['lang']) && isset($this->instance)) {
            $this->instance->setInfos(array('locale' => $configuration['lang']))->save();
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
        if (file_exists($this->getAbsolutePath() . "/conf/local.php")) {
            include $this->getAbsolutePath() . "/conf/local.php";
        }

        if ($type == 'theme') {
            $template = $this->getCurrentTheme();
            return isset($conf['tpl'][$template]) ? $conf['tpl'][$template] : array();
        }

        if (empty($conf['name']) && isset($this->instance)) {
            $conf['name'] = $this->instance->getName();
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
        $dirs = Ld_Files::getDirectories($this->getAbsolutePath() . '/lib/tpl/');

        $themes = array();
        foreach ($dirs as $name) {
            $dir = $this->getAbsolutePath() . '/lib/tpl/' . $name;
            $active = $name == $this->getCurrentTheme();
            $screenshot = $this->getSite()->getPath() . '/' . $this->getPath() . '/lib/tpl/' . $name . '/screenshot.png';
            $themes[$name] = compact('name', 'dir', 'active', 'screenshot');
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

    protected function _getLangPreference()
    {
        $dirs = Ld_Files::getDirectories($this->getAbsolutePath() . '/inc/lang/');
        $options = array();
        $options[] = array('label' => 'auto', 'value' => 'auto');
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

    public function getCustomCss()
    {
        $dir = $this->getAbsolutePath() . '/data/style';
        return Ld_Files::get($dir . '/custom.css');
    }

    public function setCustomCss($css = '')
    {
        $dir = $this->getAbsolutePath() . '/data/style';
        Ld_Files::createDirIfNotExists($dir);
        Ld_Files::put($dir . '/custom.css', $css);
        return $css;
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
            'data' => $this->getAbsolutePath() . '/data/',
            'conf' => $this->getAbsolutePath() . '/conf/'
        );
    }

    public function restore($restoreFolder)
    {
        parent::restore($restoreFolder);

        Ld_Files::unlink($this->getAbsolutePath() . '/data');

        Ld_Files::copy($this->getRestoreFolder() . '/data', $this->getAbsolutePath() . '/data');
        Ld_Files::copy($this->getRestoreFolder() . '/conf', $this->getAbsolutePath() . '/conf');

        Ld_Files::unlink($this->getRestoreFolder());

        $this->_fixUrl();
    }

    public function postMove()
    {
        $this->_fixUrl();
        $this->handleRewrite();
    }

    protected function _fixUrl()
    {
        $conf = $this->getConfiguration();
        $conf['basedir'] = $this->getSite()->getBasePath() . '/' . $this->getPath() . '/';
        $this->setConfiguration($conf);
    }

    public function postUpdate()
    {
        $this->handleRewrite();
    }

    public function postUninstall()
    {
        if (defined('LD_NGINX') && constant('LD_NGINX')) {
            $nginxDir = $this->getSite()->getDirectory('dist') . '/nginx';
            Ld_Files::rm($nginxDir . "/" . $this->getInstance()->getId() . ".conf");
        }
    }

    public function handleRewrite()
    {
        $path = $this->getSite()->getBasePath() . '/' . $this->getPath() . '/';
        if (!defined('LD_REWRITE') || constant('LD_REWRITE')) {
            $htaccess  = "RewriteEngine on\n";
            $htaccess .= "RewriteBase $path\n";
            $htaccess .= "RewriteRule ^_media/(.*)              lib/exe/fetch.php?media=$1  [QSA,L]\n";
            $htaccess .= "RewriteRule ^_detail/(.*)             lib/exe/detail.php?media=$1  [QSA,L]\n";
            $htaccess .= "RewriteRule ^_export/([^/]+)/(.*)     doku.php?do=export_$1&id=$2  [QSA,L]\n";
            $htaccess .= "RewriteRule ^$                        doku.php [L]\n";
            $htaccess .= "RewriteCond %{REQUEST_FILENAME}       !-f\n";
            $htaccess .= "RewriteCond %{REQUEST_FILENAME}       !-d\n";
            $htaccess .= "RewriteRule (.*)                      doku.php?id=$1  [QSA,L]\n";
            Ld_Files::put($this->getAbsolutePath() . "/.htaccess", $htaccess);
        }
        if (defined('LD_NGINX') && constant('LD_NGINX')) {
            $id = $this->getInstance()->getId();
            $nginxConf  = 'location {PATH} {' . "\n";
            $nginxConf .= '  index doku.php index.php;' . "\n";
            $nginxConf .= '  try_files $uri $uri/ @{ID};' . "\n";
            $nginxConf .= '}' . "\n";
            $nginxConf  = 'location ~ ^{PATH}(bin|conf|data|inc)/ {' . "\n";
            $nginxConf .= '  deny all;' . "\n";
            $nginxConf .= '}' . "\n";
            $nginxConf  = 'location @{ID} {' . "\n";
            $nginxConf .= '  rewrite ^{PATH}_media/(.*) {PATH}lib/exe/fetch.php?media=$1 last;' . "\n";
            $nginxConf .= '  rewrite ^{PATH}_detail/(.*) {PATH}lib/exe/detail.php?media=$1 last;' . "\n";
            $nginxConf .= '  rewrite ^{PATH}_export/([^/]+)/(.*) {PATH}doku.php?do=export_$1&id=$2 last;' . "\n";
            $nginxConf .= '  rewrite ^{PATH}(.*) {PATH}doku.php?id=$1&$args last;' . "\n";
            $nginxConf .= '}' . "\n";
            $nginxConf = str_replace('{ID}', $id, $nginxConf);
            $nginxConf = str_replace('{PATH}', $path, $nginxConf);
            $nginxDir = $this->getSite()->getDirectory('dist') . '/nginx';
            Ld_Files::ensureDirExists($nginxDir);
            Ld_Files::put($nginxDir . "/" . $id . ".conf", $nginxConf);
        }

    }

}
