<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Site
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Site_Local extends Ld_Site_Abstract
{

    public $id = null;

    public $type = 'local';

    public $dir = '';

    public $host = '';

    public $path = '';

    public $name = '';

    public $slots = 10;

    protected $directories = array();

    protected $_instances = array();

    protected $_users = array();

    protected $_repositories = array();

    protected $_config = array();

    public function __construct($params = array())
    {
        $properties = array('id', 'dir', 'host', 'path', 'type', 'name', 'slots', 'defaultModule');
        foreach ($properties as $key) {
            if (isset($params[$key])) {
                $this->$key = $params[$key];
            }
        }

        $this->directories = array(
            'js'     => 'js',
            'css'    => 'css',
            'shared' => 'shared',
            'lib'    => 'lib',
            'dist'   => 'dist',
            'tmp'    => 'tmp',
            'cache'  => 'tmp/cache',
            'repositories' => 'repositories'
        );

        $config = $this->getConfig();
        if (isset($config['host'])) {
            $this->host = $config['host'];
        }
        if (isset($config['path'])) {
            $this->path = $config['path'];
        }

        $this->dir = Ld_Files::real($this->dir);
    }

    public function init()
    {
        $this->_checkDirectories();
        $this->_checkConfig();
        $this->_checkRepositories();
        $this->_checkRoot();
    }

    protected function _checkDirectories()
    {
        Ld_Files::createDirIfNotExists($this->dir);

        foreach ($this->directories as $name => $directory) {
            $directory = $this->getDirectory($name);
            if (!Ld_Files::exists($directory)) {
                if (!is_writable(dirname($directory))) {
                    $msg = "Can't create folder $directory. Check your permissions.";
                    die($msg);
                }
                Ld_Files::createDirIfNotExists($directory);
            }

            if (in_array($name, array('dist', 'lib', 'tmp'))) {
                $htaccess = $directory . '/.htaccess';
                if (!Ld_Files::exists($htaccess)) {
                    Ld_Files::put($htaccess, "Deny from all");
                }
            }
        }
    }

    protected function _checkConfig()
    {
        if (!Ld_Files::exists($this->getDirectory('dist') . '/site.php')) {
            $cfg  = "<?php\n";
            if (defined('LD_DEBUG') && constant('LD_DEBUG') == true) {
                $cfg .= "define('LD_DEBUG', true);\n";
            }
            if (defined('LD_REWRITE') && constant('LD_REWRITE') == false) {
                $cfg .= "define('LD_REWRITE', false);\n";
            }
            if (defined('LD_MEMCACHED') && constant('LD_MEMCACHED') == true) {
                $cfg .= "define('LD_MEMCACHED', true);\n";
            }
            if (defined('LD_UNIX_PERMS')) {
                $cfg .= "define('LD_UNIX_PERMS', " . LD_UNIX_PERMS . ");\n";
            }
            if (defined('LD_UNIX_USER')) {
                $cfg .= "define('LD_UNIX_USER', '" . LD_UNIX_USER . "');\n";
            }
            $cfg .= '$loader = dirname(__FILE__) . "/../lib/Ld/Loader.php";' . "\n";
            $cfg .= 'if (file_exists($loader)) { require_once $loader; } else { require_once "Ld/Loader.php"; }' . "\n";
            $cfg .= "Ld_Loader::loadSite(dirname(__FILE__) . '/..');\n";
            Ld_Files::put($this->getDirectory('dist') . "/site.php", $cfg);
        }

        if (!Ld_Files::exists($this->getDirectory('dist') . '/config.json')) {
            $config = array();
            foreach (array('host', 'path', 'name') as $key) {
                if (isset($this->$key)) {
                    $config[$key] = $this->$key;
                }
            }
            $config['root_admin'] = 1;
            $config['secret'] = Ld_Auth::generatePhrase();
            Ld_Files::putJson($this->getDirectory('dist') . "/config.json", $config);
        }
    }

    protected function _checkRepositories()
    {
        if (!Ld_Files::exists($this->getDirectory('dist') . '/repositories.json')) {
            $cfg = array();
            $cfg['repositories'] = array(
                'main' => array('id' => 'main', 'name' => 'Main', 'type' => 'remote',
                'endpoint' => LD_SERVER . 'repositories/' . LD_RELEASE . '/main')
            );
            Ld_Files::putJson($this->getDirectory('dist') . '/repositories.json', $cfg);
        }
    }

    protected function _checkRoot()
    {
        // Base Index
        $root_index = $this->getDirectory() . '/index.php';
        if (!Ld_Files::exists($root_index)) {
            $index  = '<?php' . "\n";
            $index .= "define('LD_ROOT_CONTEXT', true);\n";
            $index .= "if (file_exists('admin/dispatch.php')) require_once('admin/dispatch.php');\n";
            $index .= "else echo 'La Distribution Admin component not installed.';";
            Ld_Files::put($root_index, $index);
        }

        // Base .htaccess
        // if undefined we assume it's true
        if (!defined('LD_REWRITE') || constant('LD_REWRITE')) {
            $root_htaccess = $this->getDirectory() . '/.htaccess';
            $path = $this->getPath() . '/';
            $rules = array(
                "# BEGIN LD Default",
                "<ifModule mod_rewrite.c>",
                "RewriteEngine on",
                "RewriteBase $path",
                "RewriteCond %{REQUEST_FILENAME} !-f",
                "RewriteCond %{REQUEST_FILENAME} !-d",
                "RewriteRule !\.(js|ico|gif|jpg|png|css|swf|php|txt)$ index.php",
                "</ifModule>",
                "# END LD Default"
            );
            $htaccess = Ld_Files::exists($root_htaccess) ? Ld_Files::get($root_htaccess) : '';
            if (empty($htaccess) || stripos($htaccess, 'RewriteEngine') === false) {
                if (!empty($htaccess)) { $htaccess .= "\n\n"; }
                $htaccess .= implode("\n", $rules);
                Ld_Files::put($root_htaccess, $htaccess);
            }
        }
    }

    public function isChild()
    {
        return false;
    }

    public function getUniqId()
    {
        return uniqid();
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPath()
    {
        $path = trim($this->path, " /\t\n\r\0\x0B");
        return empty($path) ? '' : '/' . $path;
    }

    public function getConfig($key = null, $default = null)
    {
        $config = $this->_config;
        if (empty($config)) {
            $config = $this->_config = Ld_Files::getJson($this->getDirectory('dist') . "/config.json");
        }
        if (isset($key)) {
            return isset($config[$key]) ? $config[$key] : $default;
        }
        return $config;
    }

    public function setConfig($param = null, $value = null)
    {
        $config = $this->getConfig();
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $config[$key] = $value;
            }
        } else if (is_string($param)) {
            $config[$param] = $value;
        }
        Ld_Files::putJson($this->getDirectory('dist') . "/config.json", $config);
        $this->_config = $config;
        return $config;
    }

    public function getBaseUrl()
    {
        return $this->getUrl();
    }

    public function getDirectory($dir = null)
    {
        if (isset($dir)) {
            $directory = $this->dir . '/' . $this->directories[$dir];
        } else {
            $directory = $this->dir;
        }
        return $directory;
    }

    public function getUrl($dir = null)
    {
        $url = 'http://' . $this->getHost() . $this->getPath() . '/';
        if (isset($dir)) {
             $url .= $this->directories[$dir];
        }
        return $url;
    }

    public function getName()
    {
        $name = $this->getConfig('name');
        if (empty($name)) {
            $name = 'La Distribution';
        }
        return $name;
    }

    public function getInstances($filterValue = null, $filterKey = 'type')
    {
        if (empty($this->_instances)) {
            $this->_instances = Ld_Files::getJson($this->getDirectory('dist') . '/instances.json');
        }

        $instances = $this->_instances;

        // Filter by type
        if (isset($filterValue)) {
            foreach ($instances as $key => $instance) {
                if (empty($instance[$filterKey]) || $filterValue != $instance[$filterKey]) {
                    unset($instances[$key]);
                }
            }
        }
        return $instances;
    }

    public function getAdmin()
    {
        $instances = $this->getInstances('admin', 'package');
        if (empty($instances)) {
            return null;
        }
        $keys = array_keys($instances);
        $id = $keys[0];
        return $this->getInstance($id);
    }

    public function getApplicationsInstances(array $ignore = array())
    {
        $applications = array();
        foreach ($this->getInstances('application') as $id => $application) {
            if (!in_array($application['package'], $ignore)) {
                $instance = $this->getInstance($id);
                if (isset($instance)) {
                    $applications[$id] = $instance;
                }
            }
        }
        return $applications;
    }

    public function updateInstances($instances)
    {
        uasort($instances, array("Ld_Utils", "sortByOrder"));
        Ld_Files::putJson($this->getDirectory('dist') . '/instances.json', $instances);
        // Reset stored instances list
        unset($this->_instances);
    }

    public function getAllLocales()
    {
        return array(
            'en_US' => 'English (USA)',
            'fr_FR' => 'Français (France)',
            // 'de_DE' => 'Deutsch (Deutschland)'
        );
    }

    public function getLocales()
    {
        $locales = Ld_Files::getJson($this->getDirectory('dist') . '/locales.json');
        if (empty($locales)) {
            $locales = array('en_US');
        }

        $list = $this->getAllLocales();
        foreach ($list as $id => $label) {
            if (!in_array($id, $locales)) {
                unset($list[$id]);
            }
        }
        return $list;
    }

    public function updateLocales($locales)
    {
        Ld_Files::putJson($this->getDirectory('dist') . '/locales.json', $locales);
    }

    public function getInstance($id)
    {
        $instances = $this->getInstances();

        // by id
        if (isset($instances[$id]) && isset($instances[$id]['path'])) {
            $dir = $this->getDirectory() . '/' . $instances[$id]['path'];

        // by global path
        } else if (Ld_Files::exists($id) && Ld_Files::exists($id . '/dist')) {
            $dir = $id;

        // by local path
        } else if (Ld_Files::exists($this->getDirectory() . '/' . $id) && Ld_Files::exists($this->getDirectory() . '/' . $id . '/dist')) {
            $dir = $this->getDirectory() . '/' . $id;

        }

        if (isset($dir)) {

            $registryKey = 'Ld_Instance_Application_Local_' . md5($dir);
            if (Zend_Registry::isRegistered($registryKey)) {
                $instance = Zend_Registry::get($registryKey);
            } else {
                $instance = Ld_Instance_Application_Local::loadFromDir($dir);
                Zend_Registry::set($registryKey, $instance);
            }

            if (isset($instance)) {
                $instance->setSite($this);
                return $instance;
            }

        }

        return null;
    }

    public function createInstance($packageId, $preferences = array())
    {
        $package = $this->getPackage($packageId);

        $installer = $package->getInstaller();

        if ($package->getType() == 'application') {
            foreach ($this->getInstances('application') as $application) {
                if ($application['path'] == $preferences['path']) {
                    throw new Exception('An application is already installed on this path.');
                }
            }
        }

        foreach ($package->getManifest()->getDependencies() as $dependency) {
            if (!$this->isPackageInstalled($dependency)) {
                $this->createInstance($dependency);
            }
        }

        $neededDb = $package->getManifest()->getDb();
        if ($neededDb && empty($preferences['db'])) {
            $availableDbs = $this->getDatabases($neededDb);
            if (empty($availableDbs)) {
                throw new Exception('No database available.');
            } else if (count($availableDbs) == 1) {
                $keys = array_keys($availableDbs);
                $preferences['db'] = $keys[0];
            } else {
                $keys = array_keys($availableDbs);
                $preferences['db'] = $keys[0];
                // throw new Exception('Can not choose Db.');
            }
        }

        if (isset($preferences['administrator']) && is_string($preferences['administrator'])) {
            $preferences['administrator'] = $this->getUser($preferences['administrator']);
            if (empty($preferences['administrator'])) {
                throw new Exception("Invalid administrator given.");
            }
        }

        switch ($package->getType()) {
            case 'bundle':
                $installer->instance = $this->createInstance($installer->application, $preferences);
                foreach ($installer->extensions as $extension) {
                    $installer->instance->addExtension($extension);
                }
                $installer->postInstall($preferences);
                return $installer->instance;
                break;
            case 'application':
                $installer->install($preferences);
                $installer->instance = $this->registerInstance($package, $preferences);
                // install available locales
                foreach ($this->getLocales() as $locale => $label) {
                    $localeId = str_replace('_', '-', strtolower($locale));
                    $localePackageId = $package->getId() . '-locale-' . $localeId;
                    $installer->instance->addExtension($localePackageId);
                }
                $installer->postInstall($preferences);
                return $installer->instance;
            default:
                $installer->install($preferences);
                $this->registerInstance($package, $preferences);
                break;
        }
    }

    public function registerInstance($package, $preferences = array())
    {
          $installer = $package->getInstaller();

          $params = array(
              'package'   => $package->getId(),
              'type'      => $package->getType(),
              'version'   => $package->getVersion()
          );

          if (isset($preferences['title'])) {
              $params['name'] = $preferences['title'];
          }

          if (isset($preferences['db'])) {
              $params['db'] = $preferences['db'];
              $params['db_prefix'] = $installer->getDbPrefix();
          }

          // Only create an instance file for applications
          if ($params['type'] == 'application') {
              $params['path'] = $installer->getPath();
              $instance = new Ld_Instance_Application_Local();
              $instance->setPath($params['path']);
              $instance->setInfos($params)->save();
          }

          $id = $this->getUniqId();

          $instances = $this->getInstances();
          $instances[$id] = array(
              'package' => $params['package'],
              'version' => $params['version'],
              'type'    => $params['type'],
              'path'    => isset($params['path']) ? $params['path'] : null,
              'name'    => isset($params['name']) ? $params['name'] : null
          );
          $this->updateInstances($instances);

          $instance = $this->getInstance($id);
          if (isset($instance)) {
              $instance->id = $id;
              return $instance;
          }
    }

    public function updateInstance($params)
    {
        if (is_string($params) && Ld_Files::exists($this->getDirectory() . '/' . $params)) { // for applications (by path)
            $instance = $this->getInstance($params);
            $packageId = $instance->getPackageId();
        } else if (is_string($params)) { // for libraries
            $packageId = $params;
        } else if (is_object($params)) { // for applications (by object)
            $instance = $params;
            $packageId = $instance->getPackageId();
        }

        $package = $this->getPackage($packageId);

        $installer = $package->getInstaller();

        // Check and eventually Update dependencies
        foreach ($package->getManifest()->getDependencies() as $dependency) {
            $infos = $this->getLibraryInfos($dependency);
            if (null === $infos) {
                $this->createInstance($dependency);
            } else {
                $dependencyPackage = $this->getPackage($dependency);
                // FIXME: this test is weak
                if ($infos['version'] != $dependencyPackage->version) {
                    $this->updateInstance($dependency);
                }
            }
        }

        // Update instance
        if (isset($instance)) {
            $installer->setInstance($instance);
            $installer->setPath($instance->getPath());
            $installer->setAbsolutePath($instance->getAbsolutePath());
        }
        $installer->update();
        $installer->postUpdate();

        // Update local registry (for applications)
        if (isset($instance) && $instance->type == 'application') {
            $instance->setInfos(array('version' => $package->version))->save();

        // Update global registry (for libraries)
        } else {
            $registeredInstances = $this->getInstances();
            foreach ($registeredInstances as $key => $registeredInstance) {
                if ($package->id == $registeredInstance['package']) {
                    $registeredInstances[$key]['version'] = $package->version;
                }
            }
            $this->updateInstances($registeredInstances);
        }

        if (isset($instance)) {
            return $instance;
        }

        // we should return an object for libraries too ...
    }

    public function moveInstance($instance, $path)
    {
        if (is_string($instance)) {
            $instance = $this->getInstance($instance);
        }

        if (empty($path)) {
            throw new Exception("Path can't be empty.");
        }

        $oldPath = $instance->getPath();

        if ($oldPath == $path) {
            return $instance;
        }

        // Move
        $installer = $instance->getInstaller();
        $installer->move($path);
        $installer->postMove();

        $instance->save();

        // God, this should be refactorised
        $registeredInstances = $this->getInstances();
        foreach ($registeredInstances as $key => $registeredInstance) {
            if ($oldPath == $registeredInstance['path']) {
                $registeredInstances[$key]['path'] = $path;
            }
        }
        $this->updateInstances($registeredInstances);

        return $instance;
    }

    public function deleteInstance($instance)
    {
        if (is_string($instance)) {
            $instance = $this->getInstance($instance);
        }

        if (empty($instance->path)) {
            throw new Exception("Path can't be empty.");
        }

        // Uninstall
        $installer = $instance->getInstaller();
        $installer->uninstall();

        // Unregister
        $instances = $this->getInstances();
        foreach ($instances as $key => $registeredInstance) {
            if (isset($registeredInstance['path']) && $instance->getPath() == $registeredInstance['path']) {
                unset($instances[$key]);
            }
        }

        $this->updateInstances($instances);
    }

    public function cloneInstance($archive, $preferences = array())
    {
        $cloneId = 'clone-' . date("d-m-Y-H-i-s");

        $restoreFolder = LD_TMP_DIR . '/' . $cloneId;
        Ld_Zip::extract($archive, $restoreFolder);

        $instanceInfos = Ld_Files::getJson($restoreFolder . '/dist/instance.json');

        $preferences['title'] = $instanceInfos['name'];

        $instance = $this->createInstance($instanceInfos['package'], $preferences);
        if (isset($instanceInfos['extensions'])) {
            foreach ($instanceInfos['extensions'] as $extension) {
                $instance->addExtension($extension['package']);
            }
        }
        $instance->restoreBackup($restoreFolder);

        return $instance;
    }

    public function getInstallPreferences($package)
    {
        if (is_string($package)) {
            $package = $this->getPackage($package);
        }

        $preferences = array();

        // Prefs
        $prefs = $package->getInstallPreferences();
        foreach ($prefs as $pref) {
            $preference = is_object($pref) ? $pref->toArray() : $pref;
            // Special Type: user
            if ($preference['type'] == 'user') {
                $users = ($admin = $this->getAdmin()) ? $admin->getUsers() : $this->getUsers();
                if (empty($users)) {
                    throw new Exception('No user available.');
                }
                $preference['type'] = 'list';
                $preference['options'] = array();
                foreach ($users as $id => $user) {
                    $preference['options'][] = array('value' => $user['username'], 'label' => !empty($user['fullname']) ? $user['fullname'] : $user['username']) ;
                }
                if (Ld_Auth::isAuthenticated()) {
                    $preference['defaultValue'] = Ld_Auth::getUsername();
                }
            }
            // Special Type: locale
            if ($preference['type'] == 'lang') {
                $preference['type'] = 'list';
                $preference['options'][] = array('value' => 'auto', 'label' => 'auto');
                $preference['options'][] = array('value' => 'en_US', 'label' => 'en_US');
                foreach ($this->getLocales() as $locale => $label) {
                    $localeId = str_replace('_', '-', strtolower($locale));
                    $localePackageId = $package->getId() . '-locale-' . $localeId;
                    if ($this->hasPackage($localePackageId)) {
                        $preference['options'][] = array('value' => $locale, 'label' => $locale);
                    }
                }
                $preference['defaultValue'] = 'auto';
            }
            $preferences[] = $preference;
        }

        // DB
        $neededDb = $package->getManifest()->getDb();
        if ($neededDb) {
            $availableDbs = $this->getDatabases($neededDb);
            if (empty($availableDbs)) {
                throw new Exception('No database available.');
            } else if (count($availableDbs) == 1) {
                $keys = array_keys($availableDbs);
                $id = $keys[0];
                $preference = array('name' => 'db', 'type' => 'hidden', 'defaultValue' => $id);
            } else {
                $preference = array('name' => 'db', 'type' => 'list', 'label' => 'Database');
                $preference['options'] = array();
                foreach ($availableDbs as $id => $db) {
                    $label = sprintf("%s", $db['name']);
                    $preference['options'][] = array('value' => $id, 'label' => $label);
                }
            }
            $preferences[] = $preference;
        }

        return $preferences;
    }

    // Databases

    public function getDatabases($type = null)
    {
        $databases = Ld_Files::getJson($this->getDirectory('dist') . '/databases.json');
        // Filter
        if (isset($type)) {
            foreach ($databases as $key => $db) {
                if ($db['type'] != $type) {
                    unset($databases[$key]);
                }
            }
        }
        return $databases;
    }

    public function addDatabase($params)
    {
        $this->_testDatabase($params);
        $databases = $this->getDatabases();
        $databases[$this->getUniqId()] = $params;
        $this->_writeDatabases($databases);
    }

    public function updateDatabase($id, $params)
    {
        $databases = $this->getDatabases();
        $databases[$id] = array_merge($databases[$id], $params);
        $this->_testDatabase($databases[$id]);
        $this->_writeDatabases($databases);
    }

    public function deleteDatabase($id)
    {
        $databases = $this->getDatabases();
        unset($databases[$id]);
        $this->_writeDatabases($databases);
    }

    protected function _writeDatabases($databases)
    {
        Ld_Files::putJson($this->getDirectory('dist') . '/databases.json', $databases);
    }

    protected function _testDatabase($db)
    {
        if (strpos($db['host'], ':')) {
            list($db['host'], $db['port']) = explode(':', $db['host']);
        }
        try {
            $con = Zend_Db::factory('Mysqli', array(
                'host'     => $db['host'],
                'username' => $db['user'],
                'password' => $db['password'],
                'dbname'   => $db['name'],
                'port'     => isset($db['port']) ? $db['port'] : null
            ));
            $result = $con->fetchCol('SHOW TABLES');
        } catch (Exception $e) {
            throw new Exception("Database parameters are incorrect.");
        }
    }

    // Users

    public function getUsersBackend()
    {
        if (empty($this->_usersBackend)) {
            $this->_usersBackend = new Ld_Site_Users_Simple();
            $this->_usersBackend->setSite($this);
        }
        return $this->_usersBackend;
    }

    public function setUsersBackend($usersBackend)
    {
        $this->_usersBackend = $usersBackend;
    }

    public function getUsers($params = array())
    {
        $users = $this->getUsersBackend()->getUsers($params);
        return $users;
    }

    public function getUsersByUsername()
    {
        $users = array();
        foreach ($this->getUsers() as $user) {
            $username = $user['username'];
            $users[$username] = $user;
        }
        return $users;
    }

    public function getUser($username)
    {
        $user = $this->getUsersBackend()->getUser($username);
        return $user;
    }

    public function getUserByUrl($url)
    {
        $user = $this->getUsersBackend()->getUserByUrl($url);
        return $user;
    }

    public function addUser($user, $validate = true)
    {
        $this->getUsersBackend()->addUser($user, $validate);
        Ld_Plugin::doAction('Site:addUser', $user);
        return $user;
    }

    public function updateUser($username, $infos = array())
    {
        $result = $this->getUsersBackend()->updateUser($username, $infos);
        Ld_Plugin::doAction('Site:updateUser', $username, $infos);
        return $result;
    }

    public function deleteUser($username)
    {
        $result = $this->getUsersBackend()->deleteUser($username);
        Ld_Plugin::doAction('Site:deleteUser', $username);
        return $result;
    }

    // Repositories

    public function getRepositories($type = null)
    {
        if (!empty($this->_repositories)) {
            return $this->_repositories;
        }

        $repositories = array();

        foreach ($this->getRepositoriesConfiguration() as $id => $config) {
            if (empty($type) || $config['type'] == $type) {
                $repositories[$id] = $this->_getRepository($config);
            }
        }

        $repositories = Ld_Plugin::applyFilters('Site:getRepositories', $repositories);

        return $this->_repositories = $repositories;
    }

    public function getRepositoriesConfiguration()
    {
        $cfg = Ld_Files::getJson($this->getDirectory('dist') . '/repositories.json');

        // LEGACY: transitional code
        if (isset($cfg['repositories'])) { $cfg = $cfg['repositories']; }

        return $cfg;
    }

    public function saveRepositoriesConfiguration($cfg)
    {
        uasort($cfg, array("Ld_Utils", "sortByOrder"));
        Ld_Files::putJson($this->getDirectory('dist') . '/repositories.json', $cfg);
        unset($this->_repositories); // empty local cache
    }

    protected function _getRepository($config)
    {
        if (isset($config['type'])) {
            $className = 'Ld_Repository_' . ucfirst(strtolower($config['type']));
            return new $className($config);
        }
    }

    public function _testRepository($config)
    {
        if ($config['type'] == 'remote') {
            if (!Zend_Uri_Http::check($config['endpoint'])) {
                 throw new Exception("Not a valid URL.");
            }
        }
        try {
            $repository = $this->_getRepository($config);
        } catch (Exception $e) {
            throw new Exception("Not a valid repository.");
        }
    }

    public function addRepository($params)
    {
        $repositories = $this->getRepositoriesConfiguration();
        $id = $this->getUniqId();
        $repositories[$id] = array(
            'id'        => $id,
            'type'      => $params['type'],
            'name'      => $params['name'],
            'endpoint'  => $params['endpoint']
        );
        $this->_testRepository($repositories[$id]);
        $this->saveRepositoriesConfiguration($repositories);
    }

    public function removeRepository($id)
    {
        $repositories = $this->getRepositoriesConfiguration();
        if (isset($repositories[$id])) {
            unset($repositories[$id]);
        }
        $this->saveRepositoriesConfiguration($repositories);
    }

    // Packages

    public function getPackages()
    {
        return $this->_getFromRepositories('packages');
    }

    public function getApplications()
    {
        return $this->_getFromRepositories('applications');
    }

    protected function _getFromRepositories($type)
    {
        $method = 'get' . ucfirst($type);
        $packages = array();
        foreach ($this->getRepositories() as $id => $repository) {
            $packages = array_merge($repository->$method(), $packages);
        }
        return $packages;
    }

    public function getPackageExtensions($packageId, $type = null)
    {
        $packages = array();
        foreach ($this->getRepositories() as $id => $repository) {
            $packages = array_merge($repository->getPackageExtensions($packageId, $type), $packages);
        }
        return $packages;
    }

    // Legacy

    public function getBasePath() { return $this->getPath(); }

}
