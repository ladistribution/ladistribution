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
        if (isset($params['package'])) {
            $this->package = $params['package'];
            $this->manifest = $this->package->getManifest();
        } elseif (isset($params['instance'])) {
            $this->instance = $params['instance'];
            $this->manifest = $this->instance->getManifest();
            $this->setPath($this->instance->getPath());
            $this->setAbsolutePath($this->instance->getAbsolutePath());
        }

        // TEMP: should be removed safely in upcoming release
        $this->site = $this->getSite();
        $this->dbPrefix = $this->getDbPrefix();
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function setAbsolutePath($path)
    {
        $this->absolutePath = $path;
    }

    // Instance / Package

    public function getSite()
    {
        if (isset($this->instance)) {
            return $this->instance->getSite();
        } elseif (isset($this->package)) {
            return $this->package->getSite();
        }
        return Zend_Registry::get('site');
    }

    public function getId()
    {
        if (isset($this->instance)) {
            return $this->instance->getPackageId();
        } elseif (isset($this->package)) {
            return $this->package->getId();
        }
        return null;
    }

    public function getDir()
    {
        return $this->package->getTmpDir();
    }

    public function getPackage()
    {
        if (isset($this->package)) {
            return $this->package;
        }
        throw new Exception("package is undefined.");
    }

    public function getInstance()
    {
        if (isset($this->instance)) {
            return $this->instance;
        }
        throw new Exception("instance is undefined.");
    }

    public function getPath()
    {
        if (isset($this->path)) {
            return $this->path;
        } else if (isset($this->instance)) {
            return $this->instance->getPath();
        }
        throw new Exception("path is undefined.");
    }

    public function getAbsolutePath()
    {
        if (isset($this->absolutePath)) {
            return $this->absolutePath;
        } else if (isset($this->instance)) {
            return $this->instance->getAbsolutePath();
        }
        throw new Exception("absolutePath is undefined.");
    }

    public function getDbPrefix()
    {
        if (isset($this->dbPrefix)) {
            return $this->dbPrefix;
        } if (isset($this->instance) && $this->instance->getType() == 'application') {
            return $this->instance->getDbPrefix();
        }
        return $this->dbPrefix = str_replace('-', '_', $this->getId()) . '_' . uniqid() . '_';
    }

    public function getManifest()
    {
        return $this->manifest;
    }

    public function getDeployments($type = null)
    {
        $rules = $this->getManifest()->getDeploymentRules();

        $deployments = array();
        foreach ($rules as $rule) {
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
                    $to = $this->getAbsolutePath() . "/" . $rule['destination'];
                    break;
                default:
                    throw new Exception('Path scheme not known.');
            }
            if ($type == 'to') {
                $deployments[] = $to;
            } else {
                $from = $this->getDir() . $rule['origin'];
                $deployments[] = compact('from', 'to');
            }
        }
        return $deployments;
    }

    public function deploy($path = null)
    {
        $this->getPackage()->fetchFiles();

        if (isset($path)) {
            $this->setPath($path);
            $this->setAbsolutePath($this->getSite()->getDirectory() . '/' . $path);
            Ld_Files::createDirIfNotExists($this->getAbsolutePath());
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
            Ld_Files::put($this->getAbsolutePath() . "/dist/config.php", $cfg_ld);
        }
    }

    public function postInstall($preferences = array()) {}

    public function update()
    {
        $this->deploy();
    }

    public function postUpdate() {}

    public function uninstall()
    {
        $absolutePath = $this->getAbsolutePath();
        if (empty($absolutePath)) {
            throw new Exception("absolutePath is undefined.");
        }

        // Erase files (would be better to delete files one by one)
        foreach ($this->getDeployments('to') as $deployment) {
            Ld_Files::unlink($deployment);
        }

        // DROP tables with current prefix
        if ($this->getManifest()->getDb() && isset($this->instance)) {
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

    // Backup / Restore

    public function getBackupDirectories() { return array(); }

    public function backup()
    {
        $timestamp = date("d-m-Y-H-i-s");
        $this->tmpFolder = LD_TMP_DIR . '/backup-' . $timestamp;
        Ld_Files::createDirIfNotExists($this->tmpFolder . '/dist');

        Ld_Files::createDirIfNotExists($this->getAbsolutePath() . '/backups');

        $directories = array('dist' => $this->getAbsolutePath() . '/dist/');
        $directories = array_merge($directories, $this->getBackupDirectories());

        $filename = 'backup-' . $timestamp . '.zip';

        $fp = fopen($this->getAbsolutePath() . '/backups/' . $filename, 'wb');
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
            $filename = $this->getAbsolutePath() . '/backups/' . $filename;
        }

        $uz = new fileUnzip($filename);
        $uz->unzipAll($this->tmpFolder);
    }

    // Configuration

    public function configure() {}

    public function getConfiguration() { return array(); }

    // Themes

    public function getThemes() { return array(); }

    // Roles

    public $defaultRole = 'user';

    public function getUserRoles()
    {
        $userRoles = Ld_Files::getJson($this->absolutePath . '/dist/roles.json');
        $users = $this->getSite()->getUsers();
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
        Ld_Files::putJson($this->getAbsolutePath() . '/dist/roles.json', $roles);
    }

    // Legacy
    public function getDependencies() { return $this->getManifest()->getDependencies(); }
    public function needDb() { return $this->getManifest()->getDb(); }
    public function getPreferences($type) { return $this->getManifest()->getPreferences($type); }
    protected function _copy($from, $to) { return Ld_Files::copy($from, $to); }
    protected function _unlink($src) { return Ld_Files::unlink($src); }
    protected function _getDirectories($dir) { return Ld_Files::getDirectories($dir); }
    protected function _generate_phrase($length = 64) { return Ld_Auth::generatePhrase($length); }

}
