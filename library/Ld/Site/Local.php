<?php

require_once 'Zend/Json.php';

require_once 'Ld/Site/Abstract.php';

require_once 'Ld/Installer/Factory.php';

require_once 'Ld/Instance/Application/Local.php';
require_once 'Ld/Instance/Extension.php';

class Ld_Site_Local extends Ld_Site_Abstract
{
    
    public $id = null;
    
    public $type = 'local';
    
    public $dir = '';
    
    public $path = '';
    
    public $basePath = '';
    
    public $name = '';
    
    public $slots = 10;
    
    public function __construct($params = array())
    {
        $properties = array('id', 'dir', 'basePath', 'type', 'name', 'slots');
        foreach ($properties as $key) {
            if (isset($params[$key])) {
                $this->$key = $params[$key];
            }
        }

        $this->directories = array(
            'js'     => $this->dir . '/js',
            'css'    => $this->dir . '/css',
            'shared' => $this->dir . '/shared',
            'lib'    => $this->dir . '/lib',
            'dist'   => $this->dir . '/dist',
            'tmp'    => $this->dir . '/dist/tmp',
            'log'    => $this->dir . '/dist/log',
            'admin'  => $this->dir . '/admin',
        );

        // transitional code
        defined('LD_ROOT') OR define('LD_ROOT', $this->dir);
        defined('LD_BASE_PATH') OR define('LD_BASE_PATH', $this->basePath);
        defined('LD_BASE_URL') OR define('LD_BASE_URL', 'http://' . LD_HOST . LD_BASE_PATH . '/');
    }

    public function init()
    {
        $this->_checkDirectories();
        $this->_checkConfig();
        $this->_checkRepositories();
    }

    protected function _checkDirectories()
    {
        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0777, true);
        }

        foreach ($this->directories as $name => $directory) {
            $constantName = strtoupper("LD_" . $name . "_DIR");
            defined($constantName) OR define($constantName, $directory);
            $directory = constant($constantName);
            if (!file_exists($directory)) {
                if (!is_writable(dirname($directory))) {
                    $msg = "Can't create folder $directory. Check your permissions.";
                    die($msg);
                }
                mkdir($directory, 0777, true);
            }
        }
    }

    protected function _checkConfig()
    {
        if (file_exists('dist/config.php')) {
            return true;
        }

        $cfg  = "<?php\n";
        $constants = array('LD_BASE_PATH');
        foreach ($constants as $name) {
          if (defined($name)) {
              $cfg .= sprintf("define('%s', '%s');\n", $name, constant($name));
          }
        }
        $cfg .= "require_once(dirname(__FILE__) . '/autoconfig.php');\n";
        Ld_Files::put($this->directories['dist'] . "/config.php", $cfg);
    }

    protected function _checkRepositories()
    {
        $cfg = array();
        $cfg['repositories'] = array(
            'main' => array('id' => 'main', 'name' => 'Main', 'type' => 'remote',
            'endpoint' => LD_SERVER . 'repositories/main')
        );
        Ld_Files::put($this->directories['dist'] . '/repositories.json', Zend_Json::encode($cfg));
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getBaseUrl()
    {
        return 'http://' . LD_HOST . $this->basePath . '/';
    }

    public function getInstances($type = null)
    {
        $json = file_get_contents($this->directories['dist'] . '/instances.json');
        $instances = Zend_json::decode($json);

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
        } else if (file_exists(LD_ROOT . '/' . $id) && file_exists(LD_ROOT . '/' . $id . '/dist')) {
            $dir = LD_ROOT . '/' . $id;
        } else if (isset($instances[$id])) {
            $dir = LD_ROOT . '/' . $instances[$id]['path'];
        }

        if (isset($dir)) {
            $instance = new Ld_Instance_Application_Local($dir);
            $instance->setSite($this);
            return $instance;
        }

        throw new Exception("can't get instance with id or path '$id'");
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
          Ld_Files::put($this->directories['dist'] . '/instances.json', Zend_Json::encode($instances));
          
          $instance = $this->getInstance($id);
          $instance->id = $id;
          return $instance;
    }

    public function updateInstance($params)
    {
        // print_r($params);
        // exit;

        if (is_string($params) && file_exists(LD_ROOT . '/' . $params)) { // for applications (by path)
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
            Ld_Files::put($this->directories['dist'] . '/instances.json', Zend_Json::encode($registeredInstances));
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
        Ld_Files::put($this->directories['dist'] . '/instances.json', Zend_Json::encode($instances));
    }

    public function restrictInstance($instance, $state = true)
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $instance));
        $installer->restrict($state);

        // Update registry
        $data = array('restricted' => $state);
        $instance->setInfos($data)->save();
    }

    public function backupInstance($instance)
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $instance));
        $installer->backup();
    }

    public function restoreBackup($instance, $archive, $absolute = false)
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $instance));
        $installer->restore($archive, $absolute);
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
                $preferences[] = array('name' => 'db', 'type' => 'hidden', 'defaultValue' => $id);
            } else {
                throw new Exception('Case not handled yet.');
            }
        }

        $prefs = $package->getInstallPreferences();
        foreach ($prefs as $pref) {
            $preference = is_object($pref) ? $pref->toArray() : $pref;
            // Special Type: user
            if ($preference['type'] == 'user') {
                $preference['type'] = 'list';
                $preference['options'] = array();
                foreach ($this->getUsers() as $id => $user) {
                    $preference['options'][] = array('value' => $user['username'], 'label' => $user['username']);
                }
                
            }
            $preferences[] = $preference;
        }

        return $preferences;
    }

    public function getBackups($instance)
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $instance));
        $archives = array();
        if (file_exists($installer->absolutePath . '/backups/')) {
            $dh = opendir($installer->absolutePath . '/backups/');
            while (false !== ($obj = readdir($dh))) {
                if (substr($obj, 0, 1) == '.') {
                    continue;
                }
                $archives[] = $obj;
            }
        }
        return $archives;
    }

    // Databases

    public function getDatabases($type = null)
    {
        $databases = array();
        $filename = $this->directories['dist'] . '/databases.json';
        if (file_exists($filename)) {
            $databases = Zend_Json::decode(file_get_contents($filename));
            // Filter
            if (isset($type)) {
                foreach ($databases as $key => $db) {
                    if ($db['type'] != $type) {
                        unset($databases[$key]);
                    }
                }
            }
        }
        return $databases;
    }

    public function addDatabase($params)
    {
        $databases = $this->getDatabases();
        
        $databases[uniqid()] = $params;
        
        $filename = $this->directories['dist'] . '/databases.json';
        Ld_Files::put($filename, Zend_Json::encode($databases));
    }

    // Users

    public function getUsers()
    {
        $users = array();
        $filename = $this->directories['dist'] . '/users.json';
        if (file_exists($filename)) {
            $users = Zend_Json::decode(file_get_contents($filename));
            foreach ($users as $key => $user) {
                $users[$key]['id'] = $key;
                $users[$key]['identities'] = array($this->getBaseUrl() . 'identity/' . $user['username']);
            }
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
        Ld_Files::put($this->directories['dist'] . '/users.json', Zend_Json::encode($users));
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
        $filename = $this->directories['dist'] . '/repositories.json';
        if (file_exists($filename)) {
            $cfg = Zend_Json::decode(file_get_contents($filename));
        } else {
            $cfg = array();
        }
        if (empty($cfg['repositories'])) {
            $cfg['repositories'] = array();
        }
        return $cfg;
    }

    public function saveRepositoriesConfiguration($cfg)
    {
        $filename = $this->directories['dist'] . '/repositories.json';
        Ld_Files::put($filename, Zend_Json::encode($cfg));  
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
        $id = strtolower($params['name']);
        if (isset($cfg['repositories'][$id])) {
            throw new Exception('Repository with this id is already existing.');
        }
        $cfg['repositories'][$id] = array(
            'id'        => $params['id'],
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
