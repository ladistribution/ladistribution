<?php

class Ld_Site_Local extends Ld_Site_Abstract
{

    public $id = null;

    public $type = 'local';

    public $dir = '';

    public $host = '';

    public $path = '';

    public $name = '';

    public $slots = 10;

    public function __construct($params = array())
    {
        $properties = array('id', 'dir', 'host', 'path', 'type', 'name', 'slots');
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
            'tmp'    => 'tmp'
        );
    }

    public function init()
    {
        $this->_checkDirectories();
        $this->_checkConfig();
        $this->_checkRepositories();
    }

    protected function _checkDirectories()
    {
        Ld_Files::createDirIfNotExists($this->dir);

        foreach ($this->directories as $name => $directory) {
            $directory = $this->getDirectory($name);
            if (!file_exists($directory)) {
                if (!is_writable(dirname($directory))) {
                    $msg = "Can't create folder $directory. Check your permissions.";
                    die($msg);
                }
                mkdir($directory, 0777, true);
            }

            if (in_array($name, array('dist', 'lib', 'tmp'))) {
                $htaccess = $directory . '/.htaccess';
                if (!file_exists($htaccess)) {
                    Ld_Files::put($htaccess, "Deny from all");
                }
            }
        }
    }

    protected function _checkConfig()
    {
        if (!file_exists($this->getDirectory('dist') . '/site.php')) {
            $cfg  = "<?php\n";
            $cfg .= '$loader = dirname(__FILE__) . "/../lib/Ld/Loader.php";' . "\n";
            $cfg .= 'if (file_exists($loader)) { require_once $loader; } else { require_once "Ld/Loader.php"; }' . "\n";
            $cfg .= "Ld_Loader::loadSite(dirname(__FILE__) . '/..');\n";
            Ld_Files::put($this->getDirectory('dist') . "/site.php", $cfg);
        }

        if (!file_exists($this->getDirectory('dist') . '/config.json')) {
            $config = array();
            foreach (array('host', 'path') as $key) {
                if (isset($this->$key)) {
                    $config[$key] = $this->$key;
                }
            }
            Ld_Files::putJson($this->getDirectory('dist') . "/config.json", $config);
        }
    }

    protected function _checkRepositories()
    {
        $cfg = array();
        $cfg['repositories'] = array(
            'main' => array('id' => 'main', 'name' => 'Main', 'type' => 'remote',
            'endpoint' => LD_SERVER . 'repositories/main')
        );
        Ld_Files::putJson($this->getDirectory('dist') . '/repositories.json', $cfg);
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getBasePath()
    {
        return $this->path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBaseUrl()
    {
        return 'http://' . $this->getHost() . $this->getPath() . '/';
    }

    public function getDirectory($dir = null)
    {
        if (isset($dir)) {
            return $this->dir . '/' . $this->directories[$dir];
        }
        return $this->dir;
    }

    public function getUrl($dir = null)
    {
        if (isset($dir)) {
            return 'http://' . $this->getHost() . $this->getPath() . '/' . $this->directories[$dir];
        }
        return 'http://' . $this->getHost() . $this->getPath() . '/';
    }

    public function getInstances($type = null)
    {
        $instances = Ld_Files::getJson($this->getDirectory('dist') . '/instances.json');

        if (empty($instances)) {
            return array();
        }

        // Filter by type
        if (isset($type)) {
            foreach ($instances as $key => $instance) {
                if (empty($instance['type']) || $type != $instance['type']) {
                    unset($instances[$key]);
                }
            }
        }

        return $instances;
    }

    public function getInstance($id)
    {
        $instances = $this->getInstances();

        if (file_exists($id) && file_exists($id . '/dist')) {
            $dir = $id;
        } else if (file_exists($this->getDirectory() . '/' . $id) && file_exists($this->getDirectory() . '/' . $id . '/dist')) {
            $dir = $this->getDirectory() . '/' . $id;
        } else if (isset($instances[$id])) {
            $dir = $this->getDirectory() . '/' . $instances[$id]['path'];
        }

        if (isset($dir)) {
            $instance = new Ld_Instance_Application_Local($dir);
            $instance->setSite($this);
            return $instance;
        }

        return null;

        // throw new Exception("can't get instance with id or path '$id'");
    }

    public function createInstance($packageId, $preferences = array())
    {
        $package = $this->getPackage($packageId);
        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));
        $installer->setSite($this);

        foreach ($installer->getDependencies() as $dependency) {
            if (null === $this->_getLibraryInfos($dependency)) {
                $this->createInstance($dependency);
            }
        }

        $neededDb = $package->getInstaller()->needDb();
        if ($neededDb && empty($preferences['db'])) {
            $availableDbs = $this->getDatabases($neededDb);
            if (empty($availableDbs)) {
                throw new Exception('No database available.');
            } else if (count($availableDbs) == 1) {
                $keys = array_keys($availableDbs);
                $preferences['db'] = $keys[0];
            } else {
                throw new Exception('Can not choose Db.');
            }
        }

        if (isset($preferences['administrator'])) {
            $preferences['administrator'] = $this->getUser($preferences['administrator']);
        }

        switch ($package->type) {
            case 'bundle':
                $instance = $this->createInstance($installer->application, $preferences);
                foreach ($installer->extensions as $extension) {
                    $this->addExtension($instance, $extension);
                }
                return $instance;
                break;
            case 'application':
                $installer->install($preferences);
                $installer->instance = $this->registerInstance($installer, $preferences);
                $installer->postInstall($preferences);
                return $installer->instance;
            default:
                $installer->install($preferences);
                $this->registerInstance($installer, $preferences);
                break;
        }
    }

    public function registerInstance($installer, $preferences = array())
    {
          $package = $this->getPackage($installer->id);

          $params = array(
              'package'   => $package->id,
              'type'      => $package->type,
              'version'   => $package->version
          );

          if (isset($preferences['title'])) {
              $params['name'] = $preferences['title'];
          }

          if (isset($preferences['db'])) {
              $params['db'] = $preferences['db'];
              $params['db_prefix'] = $installer->dbPrefix;
          }

          // Only create an instance file for applications
          if ($params['type'] == 'application') {
              $params['path'] = $installer->path;
              $instance = new Ld_Instance_Application_Local();
              $instance->setPath($params['path']);
              $instance->setInfos($params)->save();
          }

          $id = uniqid();

          $instances = $this->getInstances();
          $instances[$id] = array(
              'package' => $params['package'],
              'version' => $params['version'],
              'type'    => $params['type'],
              'path'    => isset($params['path']) ? $params['path'] : null,
              'name'    => isset($params['name']) ? $params['name'] : null
          );
          Ld_Files::putJson($this->getDirectory('dist') . '/instances.json', $instances);
          
          $instance = $this->getInstance($id);
          $instance->id = $id;
          return $instance;
    }

    public function updateInstance($params)
    {
        if (is_string($params) && file_exists($this->getDirectory() . '/' . $params)) { // for applications (by path)
            $instance = $this->getInstance($params);
            $packageId = $instance->getPackageId();
        } else if (is_string($params)) { // for libraries
            $packageId = $params;
        } else if (is_object($params)) { // for applications (by object)
            $instance = $params;
            $packageId = $instance->getPackageId();
        }

        $package = $this->getPackage($packageId);
        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));
        $installer->setSite($this);

        // Check and eventually Update dependencies
        foreach ($installer->getDependencies() as $dependency) {
            $infos = $this->_getLibraryInfos($dependency);
            if (null === $infos) {
                $this->createInstance($dependency);
            } else {
                $dependencyPackage = $this->getPackage($dependency);
                if ($infos['version'] != $dependencyPackage->version) {
                    $this->updateInstance($dependency);
                }
            }
        }

        // Update instance
        if (isset($instance)) {
            $installer->setPath($instance->getPath());
            $installer->setAbsolutePath($instance->getAbsolutePath());
        }
        $installer->update();

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
            Ld_Files::putJson($this->getDirectory('dist') . '/instances.json', $registeredInstances);
        }
        
        if (isset($instance)) {
            return $instance;
        }
        
        // we should return an object for libraries too ...
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
            if ($instance->path == $registeredInstance['path']) {
                unset($instances[$key]);
            }
        }
        Ld_Files::putJson($this->getDirectory('dist') . '/instances.json', $instances);
    }

    public function getInstallPreferences($package)
    {
        if (is_string($package)) {
            $package = $this->getPackage($package);
        }

        $preferences = array();

        // DB
        $neededDb = $package->getInstaller()->needDb();
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

        $prefs = $package->getInstaller()->getPackage()->getInstallPreferences(); // WAOW WAOW WAOW !!! :-)
        foreach ($prefs as $pref) {
            $preference = is_object($pref) ? $pref->toArray() : $pref;
            // Special Type: user
            if ($preference['type'] == 'user') {
                $users = $this->getUsers();
                if (empty($users)) {
                    throw new Exception('No user available.');
                }
                $preference['type'] = 'list';
                $preference['options'] = array();
                foreach ($users as $id => $user) {
                    $preference['options'][] = array('value' => $user['username'], 'label' => $user['username']);
                }
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $preference['defaultValue'] = $auth->getIdentity();
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
        $databases[uniqid()] = $params;
        $this->_writeDatabases($databases);
    }

    public function updateDatabase($id, $params)
    {
        $this->_testDatabase($params);
        $databases = $this->getDatabases();
        $databases[$id] = array_merge($databases[$id], $params);
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

    protected function _testDatabase($params)
    {
        try {
            $con = Zend_Db::factory('Mysqli', array(
                'host'     => $params['host'],
                'username' => $params['user'],
                'password' => $params['password'],
                'dbname'   => $params['name']
            ));
            $result = $con->fetchCol('SHOW TABLES');
        } catch (Exception $e) {
            throw new Exception("Database parameters are incorrect.");
        }
    }

    // Users

    public function getUsers()
    {
        $users = Ld_Files::getJson($this->getDirectory('dist') . '/users.json');
        foreach ((array)$users as $key => $user) {
            $users[$key]['id'] = $key;
            $users[$key]['identities'] = array($this->getBaseUrl() . 'identity/' . $user['username']);
        }
        return $users;
    }

    public function getUser($username)
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['username'] == $username) {
                return $user;
            }
        }
        return null;
    }

    public function addUser($user)
    {
        $hasher = new Ld_Auth_Hasher(8, TRUE);

        $user['hash'] = $hasher->HashPassword($user['password']);
        $username = $user['username'];

        if ($exists = $this->getUser($username)) {
            throw new Exception("User with this username already exists.");
        }

        $users = $this->getUsers();
        $users[uniqid()] = $user;

        $this->_writeUsers($users);
    }

    public function updateUser($username, $infos = array())
    {
        if (!$user = $this->getUser($username)) {
            throw new Exception("User with this username doesn't exists.");
        }
        
        $id = $user['id'];

        foreach ($infos as $key => $value) {
            $user[$key] = $value;
        }

        $users = $this->getUsers();
        $users[$id] = $user;

        $this->_writeUsers($users);
    }

    public function deleteUser($username)
    {
        if (!$user = $this->getUser($username)) {
            throw new Exception("User with this username doesn't exists.");
        }

        $id = $user['id'];

        $users = $this->getUsers();
        unset($users[$id]);

        $this->_writeUsers($users);
    }

    protected function _writeUsers($users)
    {
        Ld_Files::putJson($this->getDirectory('dist') . '/users.json', $users);
    }

    // Repositories

    public function getRepositories($type = null)
    {
        $repositories = array();

        $cfg = $this->getRepositoriesConfiguration();

        foreach ($cfg['repositories'] as $id => $config) {
            if (empty($type) || $config['type'] == $type) {
                $repositories[$id] = $this->_getRepository($config);
            }
        }

        return $repositories;
    }

    public function getRepositoriesConfiguration()
    {
        $cfg = Ld_Files::getJson($this->getDirectory('dist') . '/repositories.json');
        if (empty($cfg['repositories'])) {
            $cfg['repositories'] = array();
        }
        return $cfg;
    }

    public function saveRepositoriesConfiguration($cfg)
    {
        Ld_Files::putJson($this->getDirectory('dist') . '/repositories.json', $cfg);  
    }

    protected function _getRepository($config)
    {
        if ($config['type'] == 'local') {
            return new Ld_Repository_Local($config);
        } elseif ($config['type'] == 'remote') {
            return new Ld_Repository_Remote($config);
        }
    }

    public function addRepository($params)
    {
        $cfg = $this->getRepositoriesConfiguration();
        $id = uniqid();
        $cfg['repositories'][$id] = array(
            'id'        => $id,
            'type'      => $params['type'],
            'name'      => $params['name'],
            'endpoint'  => $params['endpoint']
        );
        $this->saveRepositoriesConfiguration($cfg);
    }

    public function removeRepository($id)
    {
        $cfg = $this->getRepositoriesConfiguration();
        if (isset($cfg['repositories'][$id])) {
            unset($cfg['repositories'][$id]);
        }
        $this->saveRepositoriesConfiguration($cfg);
    }

    // Packages

    public function getPackages()
    {
        $packages = array();
        foreach ($this->getRepositories() as $id => $repository) {
            $packages = array_merge($repository->getPackages(), $packages);
        }
        return $packages;
    }

    public function getPackageExtensions($packageId, $type = null)
    {
        $packages = array();
        foreach ($this->getRepositories() as $id => $repository) {
            $packages = array_merge($packages, $repository->getPackageExtensions($packageId, $type));
        }
        return $packages;
    }

}
