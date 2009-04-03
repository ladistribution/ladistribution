<?php

require_once 'Zend/Json.php';

require_once 'Ld/Site/Abstract.php';

require_once 'Ld/Installer/Factory.php';

require_once 'Ld/Instance/Application/Local.php';
require_once 'Ld/Instance/Extension.php';

class Ld_Site_Local extends Ld_Site_Abstract
{

    public function __construct($params = array())
    {
        $this->id = $params['id'];
        $this->type = $params['type'];
        $this->name = $params['name'];
        $this->slots = $params['slots'];
    }

    public function getInstances($type = null)
    {
        $json = file_get_contents(LD_DIST_DIR . '/instances.json');
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

    public function getInstance($path)
    {
        $instance = new Ld_Instance_Application_Local();
        $instance->setPath($path);
        $instance->setSite($this);
        return $instance;
    }

    public function createInstance($packageId, $preferences = array())
    {
        $package = $this->getPackage($packageId);
        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));

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
                $preferences['db'] = $availableDbs[0]['id'];
            } else {
                throw new Exception('Can not choose Db.');
            }
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
                $this->registerInstance($installer, $preferences);
                $instance = $this->getInstance($installer->path);
                return $instance;
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

          $instances = $this->getInstances();
          $instances[] = array(
              'package' => $params['package'],
              'version' => $params['version'],
              'type'    => $params['type'],
              'path'    => isset($params['path']) ? $params['path'] : null,
              'name'    => isset($params['name']) ? $params['name'] : null
          );
          file_put_contents(LD_DIST_DIR . '/instances.json', Zend_Json::encode($instances));
    }

    public function updateInstance($params)
    {
        if (is_string($params)) { // for libraries
            $packageId = $params;
        } else if (is_object($params)) { // for applications
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
            file_put_contents(LD_DIST_DIR . '/instances.json', Zend_Json::encode($registeredInstances));
        }
    }

    public function deleteInstance($instance)
    {
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
        file_put_contents(LD_DIST_DIR . '/instances.json', Zend_Json::encode($instances));
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

        $neededDb = $package->getInstaller()->needDb();
        if ($neededDb) {
            $availableDbs = $this->getDatabases($neededDb);
            if (empty($availableDbs)) {
                throw new Exception('No database available.');
            } else if (count($availableDbs) == 1) {
                $db = $availableDbs[0];
                $preferences[] = array('name' => 'db', 'type' => 'hidden', 'defaultValue' => $db['id']);
            } else {
                throw new Exception('Case not handled yet.');
            }
        }

        $prefs = $package->getInstallPreferences();
        foreach ($prefs as $pref) {
            $preferences[] = is_object($pref) ? $pref->toArray() : $pref;
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
        $dbs = array();
        if (file_exists(LD_DIST_DIR . '/db')) {
            $dh = opendir(LD_DIST_DIR . '/db');
            while (false !== ($obj = readdir($dh))) {
                if (substr($obj, 0, 1) == '.') {
                    continue;
                }
                $id = str_replace('.php', '', $obj);
                $array = explode('-', $id);
                if (empty($type) || $type == $array[0]) {
                    $dbs[] = array('id' => $id, 'type' => $array[0], 'host' => $array[1], 'name' => $array[2]);
                }
            }
        }
        return $dbs;
    }

    public function addDatabase($params)
    {
        $filename = $params['type'] . '-' . $params['host'] . '-' . $params['name'] . '.php';

        $cfg  = '<?php' . "\n";
        $cfg .= "define('LD_DB_HOST', '" . $params['host'] . "');\n";
        $cfg .= "define('LD_DB_NAME', '" . $params['name'] . "');\n";
        $cfg .= "define('LD_DB_USER', '" . $params['user'] . "');\n";
        $cfg .= "define('LD_DB_PASSWORD', '" . $params['password'] . "');\n";

        if (!file_exists(LD_DIST_DIR . '/db')) {
            mkdir(LD_DIST_DIR . '/db', 0777, true);
        }
        file_put_contents(LD_DIST_DIR . '/db/' . $filename, $cfg);
    }

    // Users

    public function getUsers()
    {
        if (file_exists(LD_DIST_DIR . '/users.php')) {
            $users = array();
            $file = file_get_contents(LD_DIST_DIR . '/users.php');
            $lines = explode("\n", $file);
            foreach ($lines as $line) {
                $user = explode(":", trim($line));
                if (!empty($user[0])) {
                    $username = $user[0];
                    $users[$username] = array(
                        'username'   => $user[0],
                        'password'   => $user[1],
                        'screenname' => $user[2],
                        'email'      => $user[3]
                    );
                }
            }
            return $users;
        }
        return array();
    }

    public function addUser($user)
    {
        $user['password'] = sha1($user['password']);
        $username = $user['username'];

        $users = $this->getUsers();
        $users[$username] = $user;

        $file = '';
        $htpasswd = '';
        foreach ($users as $user) {
            $file .= implode(":", $user) . "\n";
            // $htpasswd .= $user['username'] . ':' . $user['password'] . "\n";
        }
        file_put_contents(LD_DIST_DIR . '/users.php', $file);
        // file_put_contents(LD_DIST_DIR . '/.htpasswd', $htpasswd);
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
        $filename = LD_DIST_DIR . '/repositories.json';
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
        $filename = LD_DIST_DIR . '/repositories.json';
        file_put_contents($filename, Zend_Json::encode($cfg));  
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
