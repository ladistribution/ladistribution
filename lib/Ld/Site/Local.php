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
        $properties = array('id', 'dir', 'host', 'path', 'type', 'name', 'slots', 'domain', 'owner');
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
                Ld_Files::denyAccess($directory);
            }
        }
    }

    protected function _checkConfig()
    {
        if (!Ld_Files::exists($this->getDirectory('dist') . '/site.php')) {
            $cfg  = "<?php\n";

            // if (defined('LD_REWRITE') && constant('LD_REWRITE') == false) {
            //     $cfg .= "define('LD_REWRITE', false);\n";
            // }
            // if (defined('LD_UNIX_PERMS')) {
            //     $cfg .= "define('LD_UNIX_PERMS', " . LD_UNIX_PERMS . ");\n";
            // }
            // if (defined('LD_UNIX_USER')) {
            //     $cfg .= "define('LD_UNIX_USER', '" . LD_UNIX_USER . "');\n";
            // }

            // Compute a relative path
            // $relativePath = '/..';
            // if ($this->isChild()) {
            //     $path = str_replace($this->getParentSite()->getPath(), '', $this->path);
            //     $n = count(explode("/", trim($path, "/")));
            //     for ($i = 0; $i < $n; $i++) $relativePath .= '/..';
            // }

            if ($this->isChild()) {
                $cfg .= '$loader = "' . Ld_Files::real($this->getDirectory('lib')) . '/Ld/Loader.php";' . "\n";
                $cfg .= 'if (file_exists($loader)) { require_once $loader; } else { require_once "Ld/Loader.php"; }' . "\n";
                $cfg .= 'Ld_Loader::loadSite("' . $this->getParentSite()->getDirectory() . '");' . "\n";
                $cfg .= 'Ld_Loader::loadSubSite(dirname(__FILE__) . "/..");' . "\n";
            } else {
                $cfg .= '$dir = dirname(__FILE__) . "/..";' . "\n";
                $cfg .= '$loader = $dir . "/lib/Ld/Loader.php";' . "\n";
                $cfg .= 'if (file_exists($loader)) { require_once $loader; } else { require_once "Ld/Loader.php"; }' . "\n";
                $cfg .= 'Ld_Loader::loadSite($dir);' . "\n";
            }

            $cfg = Ld_Plugin::applyFilters('Site:siteFile', $cfg, $this);

            Ld_Files::put($this->getDirectory('dist') . "/site.php", $cfg);
        }

        if (!Ld_Files::exists($this->getDirectory('dist') . '/config.json')) {
            $config = array();
            foreach (array('host', 'path', 'name', 'owner') as $key) {
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
            $index .= 'define("LD_ROOT_CONTEXT", true);' . "\n";
            $index .= '$dir = dirname(__FILE__);' . "\n";
            $index .= 'if (file_exists($dir . "/dist/site.php")) {' . "\n";
            $index .= '    require_once($dir . "/dist/site.php");' . "\n";
            $index .= '    list($directory, $script) = Ld_Dispatch::dispatch();' . "\n";
            $index .= '    chdir($directory);' . "\n";
            $index .= '    require_once($script);' . "\n";
            $index .= '} else {' . "\n";
            $index .= '    echo "La Distribution not installed.";' . "\n";
            $index .= '}' . "\n";
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

    public function getHost($domain = null)
    {
        if (defined('LD_MULTI_DOMAINS') && constant('LD_MULTI_DOMAINS')) {
            if (isset($domain)) {
                $domains = $this->getDomains();
                if (isset($domains[$domain])) {
                    return $domains[$domain]['host'];
                }
            }
        }
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
        $config = Ld_Plugin::applyFilters('Site:getConfig', $config);
        if (isset($key)) {
            $value = isset($config[$key]) ? $config[$key] : $default;
            return $value;
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

    public function getBaseUrl($domain = null)
    {
        return $this->getUrl(null, $domain);
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

    public function getUrl($dir = null, $domain = null)
    {
        $url = 'http://' . $this->getHost($domain) . $this->getPath() . '/';
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

    public function getApplication($package)
    {
        $instances = $this->getInstances($package, 'package');
        if (empty($instances)) {
            return null;
        }
        $keys = array_keys($instances);
        $id = $keys[0];
        return $this->getInstance($id);
    }

    public function getAdmin()
    {
        return $this->getApplication('admin');
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
        // TODO: check if it seems to be a global path. with (strpos($id, '/') !== false)
        } else if (Ld_Files::exists($id) && Ld_Files::exists($id . '/dist')) {
            $dir = $id;

        // by local path
        } else if (Ld_Files::exists($this->getDirectory() . '/' . $id) && Ld_Files::exists($this->getDirectory() . '/' . $id . '/dist')) {
            $dir = $this->getDirectory() . '/' . $id;

        }

        if (isset($dir)) {

            $dir = Ld_Files::real($dir);

            $registryKey = 'Ld_Instance_Application_' . md5($dir);
            if (Zend_Registry::isRegistered($registryKey)) {
                $instance = Zend_Registry::get($registryKey);
            }

            if (empty($instance)) {
                $instance = Ld_Instance_Application::loadFromDir($dir);
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
                    throw new Exception(sprintf('An application is already installed on this path (%s).', $application['path']));
                }
            }
        }

        $this->checkDependencies($package);

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

          if (isset($preferences['domain'])) {
              $params['domain'] = $preferences['domain'];
          }

          if (isset($preferences['db'])) {
              $params['db'] = $preferences['db'];
              $params['db_prefix'] = $installer->getDbPrefix();
          }

          // Only create an instance file for applications
          if ($params['type'] == 'application') {
              $params['path'] = $installer->getPath();
              $instance = new Ld_Instance_Application();
              $instance->setSite($this);
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
              'name'    => isset($params['name']) ? $params['name'] : null,
              'domain'  => isset($params['domain']) ? $params['domain'] : null
          );
          $this->updateInstances($instances);

          $instance = $this->getInstance($id);
          if (isset($instance)) {
              $instance->id = $id;
              return $instance;
          }
    }

    protected function checkDependencies($package)
    {
        $ignoredDependencies = array();
        $ignoredDependencies = Ld_Plugin::applyFilters('Site:ignoredDependencies', $ignoredDependencies);
        // Check and eventually Update dependencies
        foreach ($package->getManifest()->getDependencies() as $dependency) {
            if (in_array($dependency, $ignoredDependencies)) {
                continue;
            }
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

        $this->checkDependencies($package);

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
        $instance->save();

        // Post Move
        $installer->postMove();

        // God, this should be refactorised
        $registeredInstances = $this->getInstances();
        foreach ($registeredInstances as $key => $registeredInstance) {
            if (isset($registeredInstance['path']) && $oldPath == $registeredInstance['path']) {
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

        // Empty Registry cache
        $dir = $instance->getAbsolutePath();
        $registryKey = 'Ld_Instance_Application_' . md5($dir);
        Zend_Registry::set($registryKey, null);
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
                if (count($users) == 1) {
                    $user = array_shift($users);
                    $preference['type'] = 'hidden';
                    $preference['defaultValue'] = $user['username'];
                } else {
                    $preference['type'] = 'list';
                    $preference['options'] = array();
                    foreach ($users as $id => $user) {
                        $preference['options'][] = array('value' => $user['username'],
                            'label' => !empty($user['fullname']) ? $user['fullname'] : $user['username']) ;
                    }
                    if (Ld_Auth::isAuthenticated()) {
                        $preference['defaultValue'] = Ld_Auth::getUsername();
                    }
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

        // Domain
        if (defined('LD_MULTI_DOMAINS') && constant('LD_MULTI_DOMAINS') && $package->getType() == 'application') {
            $domains = $this->getDomains();
            if (count($domains) == 1) {
                // $keys = array_keys($domains);
                // $id = $keys[0];
                // $domainPreference = array('name' => 'domain', 'type' => 'hidden', 'defaultValue' => $id);
            } else {
                $domainPreference = array('type' => 'list', 'name' => 'domain', 'label' => 'Domain', 'options' => array());
                foreach ($domains as $id => $domain) {
                    $domainPreference['options'][] = array('value' => $id, 'label' => $domain['host']);
                    if ($domain['host'] == $this->getConfig('host')) {
                        $domainPreference['defaultValue'] = $id;
                    }
                }
                $preferences[] = $domainPreference;
            }
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
        $this->testDatabase($params);
        $databases = $this->getDatabases();
        $databases[$this->getUniqId()] = $params;
        $this->_writeDatabases($databases);
    }

    public function updateDatabase($id, $params)
    {
        $databases = $this->getDatabases();
        $databases[$id] = array_merge($databases[$id], $params);
        $this->testDatabase($databases[$id]);
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

    public function testDatabase($dbParameters, $throwException = true)
    {
        try {
            $con = Ld_Utils::getDbConnection($dbParameters, 'zend');
            $con->fetchCol('SHOW TABLES');
            return true;
        } catch (Exception $e) {
            if ($throwException) {
                throw new Exception("Can't connect to database: " . $e->getMessage());
            } else {
                return false;
            }
        }
    }

    public function isDatabaseUsed($id)
    {
        $n = 0;
        $instances = $this->getApplicationsInstances();
        foreach ($instances as $instance) {
            $infos = $instance->getInfos();
            if (isset($infos['db']) && $infos['db'] == $id) {
                $n++;
            }
        }
        return $n;
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
        if (empty($user) && Zend_Uri_Http::check($username)) {
            $user = $this->getUserByUrl($username);
        }
        if (empty($user)) {
            $user = $this->getUsersBackend()->getUserBy('fullname', $username);
        }
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

        // Register User in Admin (may be plugged as an app plugin)
        if ($admin = $this->getAdmin()) {
            $username = $user['username'];
            $administrators = $admin->getAdministrators();
            if (count($administrators) == 0) {
                $roles = array_merge($admin->getUserRoles(), array($username => 'admin'));
                $admin->setUserRoles($roles);
            } else {
                $roles = array_merge($admin->getUserRoles(), array($username => 'user'));
                $admin->setUserRoles($roles);
            }
        }

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

        $cfg = Ld_Plugin::applyFilters('Site:getRepositoriesConfiguration', $cfg);

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

    // Plugins

    public function getPlugins()
    {
        $plugins = array();
        $plugin_files = Ld_Files::getFiles($this->getDirectory('shared') . '/plugins');
        foreach ($plugin_files as $fileName) {
            $id = strtolower(str_replace('.php', '', $fileName));
            $plugins[$id] = array(
                'className' => 'Ld_Plugin_' . Zend_Filter::filterStatic($id, 'Word_DashToCamelCase')
            );
        }

        $plugins = Ld_Plugin::applyFilters('Site:plugins', $plugins);

        ksort($plugins);

        return $plugins;
    }

    // Sites

    public function getSites($type = null)
    {
        $sites = Ld_Files::getJson($this->getDirectory('dist') . '/sites.json');
        if (empty($sites)) {
            $sites = array();
        }
        return $sites;
    }

    public function getSite($id)
    {
        $sites = $this->getSites();
        if (isset($sites[$id])) {
          return $sites[$id];
        }
        return null;
    }

    public function addSite($params)
    {
        $sites = $this->getSites();
        $sites[$this->getUniqId()] = $params;
        $this->_writeSites($sites);
    }

    public function updateSite($id, $params)
    {
        $sites = $this->getSites();
        $sites[$id] = array_merge($sites[$id], $params);
        $this->_writeSites($sites);
        return $sites[$id];
    }

    public function deleteSite($id)
    {
        $sites = $this->getSites();
        unset($sites[$id]);
        $this->_writeSites($sites);
    }

    protected function _writeSites($sites)
    {
        Ld_Files::putJson($this->getDirectory('dist') . '/sites.json', $sites);
    }

    // Domains

    public function getDomains()
    {
        $domains = Ld_Files::getJson($this->getDirectory('dist') . '/domains.json');
        // transitional code
        if (empty($domains)) {
            $domains = array();
            $id = $this->getUniqId();
            $domains[$id] = array(
                'host' => $this->getConfig('host'),
                'default_application' => $this->getConfig('root_application')
            );
            $this->writeDomains($domains);
        }
        return $domains;
    }

    public function getDomain($id)
    {
        $domains = $this->getDomains();
        if (isset($domains[$id])) {
            return $domains[$id];
        }
        return null;
    }

    public function addDomain($params)
    {
        $domains = $this->getDomains();
        $id = $this->getUniqId();
        $domains[$id] = $params;
        $this->writeDomains($domains);
    }

    public function updateDomain($id, $params)
    {
        $domains = $this->getDomains();
        $domains[$id] = array_merge($domains[$id], $params);
        $this->writeDomains($domains);
    }

    public function deleteDomain($id)
    {
        $domains = $this->getDomains();
        unset($domains[$id]);
        $this->writeDomains($domains);
    }

    public function writeDomains($domains)
    {
        uasort($domains, array("Ld_Utils", "sortByOrder"));
        Ld_Files::putJson($this->getDirectory('dist') . '/domains.json', $domains);
    }

    // Colors

    public function getColors()
    {
        $default = Ld_Ui::getDefaultSiteColors();
        $stored = Ld_Files::getJson($this->getDirectory('dist') . '/colors.json');
        $colors = Ld_Ui::computeColors($default, $stored);
        return $colors;
    }

    public function setColors($colors = array())
    {
        $filename = $this->getDirectory('dist') . '/colors.json';
        Ld_Files::putJson($filename, $colors);
        $this->_updateAppearanceVersion();
    }

    public function getCustomCss()
    {
        $css = Ld_Files::get($this->getDirectory('dist') . '/custom.css');
        return $css ? $css : '';
    }

    public function setCustomCss($css = '')
    {
        Ld_Files::put($this->getDirectory('dist') . '/custom.css', $css);
        $this->_updateAppearanceVersion();
    }

    protected function _updateAppearanceVersion()
    {
        $version = substr(md5(time()), 0, 10);
        $this->setConfig('appearance_version', $version);
    }

    // Legacy

    public function getBasePath() { return $this->getPath(); }

}
