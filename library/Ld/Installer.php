<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Installer
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Installer
{

    public function __construct($params = array())
    {
        $this->dir = $params['dir'];

        $this->id = isset($params['id']) ? $params['id'] : null;
        $this->instance = isset($params['instance']) ? $params['instance'] : null;

        if (isset($this->instance)) {
            $this->setPath($this->instance->getPath());
            $this->setAbsolutePath($this->instance->getAbsolutePath());
        }

        if (empty($params['dbPrefix'])) {
            $this->dbPrefix = str_replace('-', '_', $this->id) . '_' . uniqid() . '_';
        } else {
            $this->dbPrefix = $params['dbPrefix'];
        }

        $filename = $this->dir . '/dist/manifest.xml';
        if (!file_exists($filename)) {
            $filename = $this->dir . '/manifest.xml'; // alternate name
        }
        if (file_exists($filename)) {
            $this->package = new Ld_Package(array('manifest' => $filename));
            $this->manifest = $this->package->getManifest();
        } else {
            throw new Exception("manifest.xml doesn't exists or is unreadable in $this->dir");
        }
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function setAbsolutePath($path)
    {
        $this->absolutePath = $path;
    }

    public function getSite()
    {
        if (isset($this->site)) {
            return $this->site;
        }
        return Zend_Registry::get('site');
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function getPackage()
    {
        return $this->package;
    }

    public function getManifest()
    {
        return $this->manifest;
    }

    public function getDependencies()
    {
        $dependencies = array();
        foreach ($this->manifest->need as $need) {
            $dependencies[] = (string)$need;
        }
        return $dependencies;
    }

    public function getDeployments()
    {
        // Default Rules

        $type = (string)$this->manifest->type;

        $rules = array();

        switch ($type) {
            case 'application':
            case 'theme':
            case 'plugin':
            case 'locale':
                $rules[$type] = array('origin' => $type, 'path' => 'public', 'destination' => '');
                $rules['dist'] = array('origin' => 'dist', 'path' => 'public', 'destination' => 'dist');
            default:
        }

        foreach ($this->manifest->deploy as $deploy) {
            $id = (string)$deploy->origin;
            $rules[$id] = array(
                'origin' => (string)$deploy->origin,
                'path' => (string)$deploy->destination['path'],
                'destination' => (string)$deploy->destination
            );
        }

        $deployments = array();
        foreach ($rules as $rule) {
            $from = $this->dir . $rule['origin'];
            switch ($rule['path']) {
                case 'lib':
                    $to = $this->getSite()->getDirectory('lib') . "/" . $rule['destination'];
                    break;
                case 'css':
                    $to = $this->getSite()->getDirectory('css') . "/" . $rule['destination'];
                    break;
                case 'js':
                    $to = $this->getSite()->getDirectory('js') . "/" . $rule['destination'];
                    break;
                case 'shared':
                    $to = $this->getSite()->getDirectory('shared') . "/" . $rule['destination'];
                    break;
                case 'public':
                    $to = $this->absolutePath . "/" . $rule['destination'];
                    break;
                default:
                    throw new Exception('Path scheme not known.');
            }
            $deployments[] = compact('from', 'to');
        }
        return $deployments;
    }

    public function getExtendedPath()
    {
        if (isset($this->manifest->directory)) {
            return (string)$this->manifest->directory;
        }
        return null;
    }

    public function deploy($path = null)
    {
        if (isset($path)) {
            $this->setPath($path);
            $this->setAbsolutePath($this->getSite()->getDirectory() . '/' . $path);
            Ld_Files::createDirIfNotExists($this->absolutePath);
        }

        foreach ($this->getDeployments() as $deployment) {
            Ld_Files::copy($deployment['from'], $deployment['to']);
        }
    }

    public function install($preferences = array())
    {
        if (!isset($preferences['path'])) {
            $preferences['path'] = null;
        }

        $this->deploy($preferences['path']);

        // Config
        if (isset($preferences['path'])) {
            $cfg_ld = "<?php\n";
            $cfg_ld .= "require_once('" . realpath($this->getSite()->getDirectory('dist')) . "/site.php');\n";
            Ld_Files::put($this->absolutePath . "/dist/config.php", $cfg_ld);
        }
    }

    public function postInstall($preferences = array()) {}

    public function update()
    {
        $this->deploy();
    }

    public function postUpdate($preferences = array()) {}

    public function uninstall()
    {
        if (empty($this->absolutePath)) {
            throw new Exception("Path is undefined");
        }

        // Erase files (would be better to delete files one by one)
        foreach ($this->getDeployments() as $deployment) {
            Ld_Files::unlink($deployment['to']);
        }

        // DROP tables with current prefix
        if ($this->needDb() && isset($this->instance)) {
            $db = $this->instance->getDbConnection();
            $dbPrefix = $this->instance->getDbPrefix();
            $result = $db->fetchCol('SHOW TABLES');
            foreach ($result as $tablename) {
                if (strpos($tablename, $dbPrefix) !== false) {
                    $db->query("DROP TABLE $tablename");
                }
            }
        }
    }

    public function backup()
    {
        $timestamp = date("d-m-Y-H-i-s");
        $this->tmpFolder = LD_TMP_DIR . '/backup-' . $timestamp;
        Ld_Files::createDirIfNotExists($this->tmpFolder . '/dist');

        Ld_Files::createDirIfNotExists($this->absolutePath . '/backups');

        $directories = array('dist' => $this->absolutePath . '/dist/');
        $directories = array_merge($directories, $this->getBackupDirectories());

        $filename = 'backup-' . $timestamp . '.zip';

        $fp = fopen($this->absolutePath . '/backups/' . $filename, 'wb');
        $zip = new fileZip($fp);
        foreach ($directories as $name => $directory) {
            if (file_exists($directory)) {
                $zip->addDirectory($directory, $name, true);
            }
        }
        $zip->write();
        unset($zip);

        Ld_Files::unlink($this->tmpFolder);
    }

    public function restore($filename, $absolute = false)
    {
        $timestamp = date("d-m-Y-H-i-s");
        $this->tmpFolder = LD_TMP_DIR . '/backup-' . $timestamp;

        if ($absolute == false) {
            $filename = $this->absolutePath . '/backups/' . $filename;
        }

        $uz = new fileUnzip($filename);
        $uz->unzipAll($this->tmpFolder);
    }

    public function needDb()
    {
        if (isset($this->manifest->db)) {
            return (string) $this->manifest->db;
        }
        return false;
    }

    public function configure() {}

    public function getConfiguration() { return array(); }

    public function getThemes() { return array(); }

    public function getBackupDirectories() { return array(); }

    // Roles

    public $defaultRole = 'user';

    public function getUserRoles()
    {
        $userRoles = array();
        $filename = $this->absolutePath . '/dist/roles.json';
        if (file_exists($filename)) {
            $json = Ld_Files::get($filename);
            $userRoles = Zend_Json::decode($json);
        }
        $users = $this->site->getUsers();
        foreach ((array)$users as $user) {
            $username = $user['username'];
            if (empty($userRoles[$username])) {
                $userRoles[$username] = $this->defaultRole;
            }
        }
        return $userRoles;
    }

    public function setUserRoles($roles)
    {
        $filename = $this->absolutePath . '/dist/roles.json';
        $json = Zend_Json::encode($roles);
        Ld_Files::put($filename, $json);
    }

    // Legacy
    public function getPreferences($type) { return $this->package->getPreferences($type); }
    protected function _copy($from, $to) { return Ld_Files::copy($from, $to); }
    protected function _unlink($src) { return Ld_Files::unlink($src); }
    protected function _getDirectories($dir) { return Ld_Files::getDirectories($dir); }
    protected function _generate_phrase($length = 64) { return Ld_Auth::generatePhrase($length); }

}
