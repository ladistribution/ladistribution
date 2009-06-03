<?php

class Ld_Installer_Habari extends Ld_Installer
{

    public function autoload($class_name)
    {
        $file_name = strtolower($class_name) . '.php';
        $dirs = array($this->absolutePath . '/system/classes/');
        foreach ($dirs as $dir) {
            if (file_exists($dir . $file_name)) {
                require_once $dir . $file_name;
            }
        }
    }

    protected function load_habari()
    {
        if (empty($this->loaded)) {
            spl_autoload_register(array($this, 'autoload'));
            defined('DEBUG') OR define('DEBUG', true);
            defined('HABARI_PATH') OR define('HABARI_PATH', $this->absolutePath);
            $config = Site::get_dir('config_file');
            require_once $config;
            DB::connect();
            Site::$habari_url = $this->instance->getUrl();
            require_once $this->absolutePath . '/system/classes/habarilocale.php';
            require_once $this->absolutePath . '/system/classes/habaridatetime.php';
            HabariDateTime::set_default_timezone('UTC');
            $this->loaded = true;
        }
    }

    public function postInstall($preferences = array())
    {
        $this->httpClient = new Zend_Http_Client();
        $this->httpClient->setCookieJar();

        if (isset($preferences['administrator'])) {
            $preferences['admin_username'] = $preferences['administrator']['username'];
            $preferences['admin_username'] = $preferences['administrator']['username'];
            $preferences['admin_email'] = $preferences['administrator']['email'];
            $preferences['admin_password'] = $this->_generate_phrase(10);
        }

        $parameters = array(
            'admin_email'     => $preferences['admin_email'],
            'admin_pass1'     => $preferences['admin_password'],
            'admin_pass2'     => $preferences['admin_password'],
            'admin_username'  => $preferences['admin_username'],
            'blog_title'      => $preferences['title'],
            'db_file'         => 'habari.db',
            'db_type'         => 'sqlite',
            'table_prefix'    => $this->dbPrefix,
            'locale'          => 'en-us',
            'submit'          => 'Install Habari',
        );

        $plugins = array(
            '/system/plugins/coredashmodules/coredashmodules.plugin.php',
            '/system/plugins/habarisilo/habarisilo.plugin.php',
            '/system/plugins/pingback/pingback.plugin.php',
            '/system/plugins/spamchecker/spamchecker.plugin.php',
            '/system/plugins/undelete/undelete.plugin.php',
            '/system/plugins/ld/ld.plugin.php'
        );

        foreach ($plugins as $plugin) {
            $file = realpath($this->absolutePath) . $plugin;
            $plugin_id = $this->id_from_file( $file );
            $parameters["plugin_$plugin_id"] = "On";
        }

        $this->httpClient->setUri($this->instance->getUrl());
        $this->httpClient->setParameterPost($parameters);
        $response = $this->httpClient->request('POST');

        $this->createConfigFile($parameters);
    }

    public function createConfigFile($parameters)
    {
        $cfg  = "<?php\n";
        $cfg .= "require_once(dirname(__FILE__) . '/dist/config.php');\n";
        $cfg .= "Config::set('db_connection', array(\n";
        $cfg .= sprintf(
            "'connection_string'=>'%s:%s', 'username'=>'','password'=>'', 'prefix'=>'%s'",
            $parameters['db_type'], $parameters['db_file'], $parameters['table_prefix']
        );
        $cfg .= "));\n";
        $cfg .= "?>";

        Ld_Files::put($this->absolutePath . "/config.php", $cfg);
    }

    // Themes

    public function getThemes()
    {
        $this->load_habari();
        $active_theme = Themes::get_active();
        $themes = array();
        foreach( Themes::get_all_data() as $id => $theme ) {
            $name = (string)$theme['info']->name;
            $active = $active_theme->name == $name;
            $screenshot = $theme['screenshot'];
            $themes[$id] = compact('name', 'screenshot', 'active');
        }
        return $themes;
    }

    public function setTheme($theme)
    {
        foreach ($this->getThemes() as $id => $t) {
            if ($theme == $id) {
                Themes::activate_theme($t['name'],  $id);
                return true;
            }
        }
    }

    // Roles

    public $defaultRole = 'authenticated';

    public function getRoles()
    {
        $this->load_habari();
        $roles = array();
        foreach (UserGroups::get() as $group) {
            $roles[] = $group->name;
        }
        return $roles;
    }

    public function getUserRoles()
    {
        $this->load_habari();
        $user_roles = array();
        foreach ($this->site->getUsers() as $user) {
            $username = $user['username'];
            $user_roles[$username] = $this->defaultRole; // default
            $habari_user = User::get_by_name($username);
            if (!empty($habari_user)) {
                foreach ($this->getRoles() as $role) {
                    if ($habari_user->in_group($role)) {
                        $user_roles[$username] = $role;
                    }
                }
            }
        }
        return $user_roles;
    }

    public function setUserRoles()
    {

    }

    protected static function id_from_file( $file )
    {
        $file = str_replace(array('\\', '/'), PATH_SEPARATOR, $file);
        return sprintf( '%x', crc32( $file ) );
    }

}
