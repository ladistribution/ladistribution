<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Installer
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Installer
{

    protected $_backupDirectories = array();

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
        return $this->getPackage()->getTmpDir();
    }

    public function getPackage()
    {
        if (isset($this->package)) {
            return $this->package;
        }
        throw new Exception("package is undefined.");
    }

    public function setInstance($instance)
    {
        $this->instance = $instance;
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
        if (isset($this->manifest)) {
            return $this->manifest;
        }
        throw new Exception("manifest is undefined.");
    }

    public function getLinks()
    {
        if (isset($this->instance)) {
            $baseUrl = substr($this->getInstance()->getUrl(), 0, -1);
        } else {
            $baseUrl = $this->getSite()->getUrl() . $this->getPath();
        }

        $links = $this->getManifest()->getLinks();
        foreach ($links as $id => $link) {
            $links[$id]['href'] = $baseUrl . $link['href'];
        }
        return $links;
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
            $this->_createDistConfigFile($preferences['path']);
        }
    }

    public function postInstall($preferences = array()) {}

    protected function _createDistConfigFile($path)
    {
        $cfg_ld = "<?php\n";

        // Compute a relative path
        $n = count(explode("/", trim($path, "/")));
        for ($i = 0; $i < $n; $i++) $relativePath = isset($relativePath) ? $relativePath . '/..' : '/../..';
        $cfg_ld .= "require_once(dirname(__FILE__) . '$relativePath/dist/site.php');\n";

        // The same code with an absolute path
        // $cfg_ld .= "require_once('" . realpath($this->getSite()->getDirectory('dist')) . "/site.php');\n";

        Ld_Files::put($this->getAbsolutePath() . "/dist/config.php", $cfg_ld);
    }

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
        foreach (array_reverse($this->getDeployments('to')) as $deployment) {
            Ld_Files::unlink($deployment);
        }

        // DROP tables
        if ($this->getManifest()->getDb()) {
            foreach ($this->getInstance()->getDbTables() as $tablename) {
                $db = $this->getInstance()->getDbConnection();
                $db->query("DROP TABLE IF EXISTS $tablename");
            }
        }
    }

    // Backup / Restore

    protected function _escapeCsv($string)
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = addcslashes($string, '"');
        return '"' . $string . '"';
    }

    public function getBackupDirectories()
    {
        if ($this->getManifest()->getDb() && $dbConnection = $this->getInstance()->getDbConnection('php')) {

            Ld_Files::createDirIfNotExists($this->getBackupFolder() . '/tables');

            // Generate SQL schema
            $fp = fopen($this->getBackupFolder() . "/tables/schema.sql", "w");
            foreach ($this->getInstance()->getDbTables() as $tablename) {
                $drop = "DROP TABLE IF EXISTS `" . $tablename . "`;\n";
                $result = $dbConnection->query("SHOW CREATE TABLE $tablename")->fetch_array();
                $create = $result[1] . ";\n";
                fwrite($fp, $drop);
                fwrite($fp, $create);
            }
            fclose($fp);

            // Generate data CSVs
            foreach ($this->getInstance()->getDbTables() as $id => $tablename) {
                $result = $dbConnection->query("SELECT * FROM $tablename");
                if (!empty($result)) {
                    $csv = '';
                    while ($row = $result->fetch_assoc()) {
                        $row = array_map(array($this, '_escapeCsv'), $row);
                        $csv .= implode(";", $row) . "\n";
                    }
                    if (!empty($csv)) {
                        Ld_Files::put($this->getBackupFolder() . "/tables/$id.csv", $csv);
                    }
                }
            }

            $this->_backupDirectories['tables'] = $this->getBackupFolder() . '/tables/';

        }

        $this->_backupDirectories['dist'] = $this->getAbsolutePath() . '/dist/';

        return $this->_backupDirectories;
    }

    public function getBackupFolder()
    {
        if (empty($this->tmpFolder)) {
            $this->tmpFolder = LD_TMP_DIR . '/backup-' . date("d-m-Y-H-i-s");
        }
        return $this->tmpFolder;
    }

    public function getRestoreFolder()
    {
        if (empty($this->restoreFolder)) {
            $this->restoreFolder = LD_TMP_DIR . '/restore-' . date("d-m-Y-H-i-s");
        }
        return $this->restoreFolder;
    }

    public function getBackupsPath()
    {
        return $this->getSite()->getDirectory('dist') . '/backups/' . $this->getInstance()->getId();
        // old emplacement
        // return $this->getAbsolutePath() . '/backups';
    }

    public function backup()
    {
        $backupsPath = $this->getBackupsPath();

        Ld_Files::createDirIfNotExists($backupsPath);

        $directories = array('dist' => $this->getAbsolutePath() . '/dist/');
        $directories = array_merge($directories, $this->getBackupDirectories());

        $filename = /* 'backup-' . */ $this->getId() . '-' . $this->getInstance()->getId() . '-' . date("Y-m-d-H-i-s") . '.zip';

        Ld_Zip::pack($directories, $backupsPath . '/' . $filename);

        Ld_Files::unlink($this->getBackupFolder());
    }

    public function move($path)
    {
        Ld_Files::move($this->getInstance()->getAbsolutePath(), $this->getSite()->getDirectory() . '/' . $path);

        $this->getInstance()->setInfos(array('path' => $path));

        $this->setPath($this->getInstance()->getPath());
        $this->setAbsolutePath($this->getInstance()->getAbsolutePath());

        $this->_createDistConfigFile($path);
    }

    public function postMove() {}

    public function restore($archive)
    {
        $filename = $this->getBackupsPath() . '/' . $archive;
        if (is_file($filename)) {
            $this->restoreFolder = $this->tmpFolder = $this->getRestoreFolder();
            Ld_Zip::extract($filename, $this->restoreFolder);
        } elseif (is_dir($archive)) {
            $this->restoreFolder = $this->tmpFolder = $archive;
        }

        if ($this->getManifest()->getDb() && $dbConnection = $this->getInstance()->getDbConnection('php')) {

            foreach ($this->getInstance()->getDbTables() as $id => $tablename) {
                $result = $dbConnection->query("TRUNCATE TABLE  $tablename");
                $filename = $this->getRestoreFolder() . '/tables/' . $id . '.csv';
                // not yet clear when we have to use
                // CHARACTER SET utf8
                // or not :-/
                if (Ld_Files::exists($filename)) {
                    $query = "LOAD DATA LOCAL INFILE '$filename'
                    REPLACE INTO TABLE $tablename
                    FIELDS TERMINATED BY ';'
                    ENCLOSED BY '\"'
                    ESCAPED BY '\\\\'
                    LINES TERMINATED BY '\n'"; // IGNORE 1 LINES;
                    $result = $dbConnection->query($query);
                    if (!$result) {
                        throw new Exception($dbConnection->error);
                    }
                }
            }

        }

    }

    // Configuration

    public function getConfiguration()
    {
        $configuration = Ld_Files::getJson($this->getAbsolutePath() . '/dist/configuration.json');
        $instance = $this->getInstance();
        if (empty($configuration['name']) && isset($instance)) {
            $configuration['name'] = $this->getInstance()->getName();
        }
        if (empty($configuration['locale']) && isset($instance)) {
            $configuration['locale'] = $this->getInstance()->getLocale();
        }
        return $configuration;
    }

    public function setConfiguration($configuration)
    {
        $instance = $this->getInstance();
        if (isset($configuration['name']) && isset($instance)) {
            $instance->setInfos(array('name' => $configuration['name']))->save();
        }
        if (isset($configuration['locale']) && isset($this->instance)) {
            $this->instance->setInfos(array('locale' => $configuration['locale']))->save();
        }
        Ld_Files::putJson($this->getAbsolutePath() . '/dist/configuration.json', $configuration);
        return $configuration;
    }

    // Themes

    public function getThemes() { return array(); }

    public function getPreferences($type)
    {
        try {
            switch ($type) {
                case 'theme':
                    return $this->getThemePreferences();
                default:
                    $manifest = $this->getManifest();
                    return $manifest->getPreferences($type);
            }
        } catch (Exception $e) {
            return array();
        }
    }

    public function getThemePreferences()
    {
        foreach ($this->getThemes() as $theme) {
            if ($theme['active']) {
                try {
                    $manifest = Ld_Manifest::loadFromDirectory($theme['dir']);
                    return $manifest->getPreferences();
                } catch (Exception $e) {
                    break;
                }
            }
        }
        return array();
    }

    // Roles

    public $defaultRole = 'user';

    public function getUserRoles()
    {
        $userRoles = Ld_Files::getJson($this->getAbsolutePath() . '/dist/roles.json');
        return $userRoles;
    }

    public function setUserRoles($roles)
    {
        Ld_Files::putJson($this->getAbsolutePath() . '/dist/roles.json', $roles);
    }

    public function getUserOrder()
    {
        $userOrder = Ld_Files::getJson($this->getAbsolutePath() . '/dist/user-order.json');
        return (array)$userOrder;
    }

    public function setUserOrder($userOrder)
    {
        Ld_Files::putJson($this->getAbsolutePath() . '/dist/user-order.json', $userOrder);
    }

    public function getUserRole($username)
    {
        $userRoles = $this->getUserRoles();
        if (isset($username) && isset($userRoles[$username])) {
            return $userRoles[$username];
        }
        return $this->defaultRole;
    }

    // Legacy
    public function getDependencies() { return $this->getManifest()->getDependencies(); }
    public function needDb() { return $this->getManifest()->getDb(); }
    protected function _copy($from, $to) { return Ld_Files::copy($from, $to); }
    protected function _unlink($src) { return Ld_Files::unlink($src); }
    protected function _getDirectories($dir) { return Ld_Files::getDirectories($dir); }
    protected function _generate_phrase($length = 64) { return Ld_Auth::generatePhrase($length); }

}
