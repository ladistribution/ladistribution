<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Instance
 * @subpackage Ld_Instance_Application
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Instance_Application_Local extends Ld_Instance_Application_Abstract
{

    protected $_dbConnections = array();

    protected $absolutePath;

    protected $instanceJson;

    public static function loadFromDir($directory)
    {
        $params = Ld_Files::getJson($directory . '/dist/instance.json');
        if (!empty($params)) {
            return new Ld_Instance_Application_Local($params);
        }
        return null;
    }

    public function getInfos()
    {
        if (empty($this->infos)) {

            if (!file_exists($this->getAbsolutePath())) {
                throw new Exception("no application found in path $this->absolutePath");
            }

            if (!file_exists($this->getInstanceJson())) {
                throw new Exception("instance.json not found in path $this->absolutePath");
            }

            $json = file_get_contents($this->getInstanceJson());
            $this->setInfos(Zend_json::decode($json));

            if (empty($this->infos['path'])) {
                throw new Exception('Path empty. Is it an application?');
            }

        }

        return $this->infos;
    }

    public function save()
    {
        Ld_Files::putJson($this->getInstanceJson(), $this->infos);
    }

    public function setSite($site)
    {
        parent::setSite($site);

        if ($this->type == 'application' && isset($this->path)) {
            $this->infos['url'] = $this->url = $this->site->getBaseUrl() . $this->path . '/';
        }
    }

    public function setPath($path)
    {
        $this->path = $path;
        $this->absolutePath = $this->getSite()->getDirectory() . '/' . $path;
    }

    public function getAbsolutePath()
    {
        return $this->absolutePath = $this->getSite()->getDirectory() . '/' . $this->path;
    }

    public function getInstanceJson()
    {
        return $this->getAbsolutePath() . '/dist/instance.json';
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getDbPrefix()
    {
        $infos = $this->getInfos();
        if (isset($infos['db_prefix'])) {
            return $infos['db_prefix'];
        }
        return null;
    }

    public function getDbTables()
    {
        $tables = array();
        if ($this->getManifest()->getDb()) {
            $db = $this->getDbConnection();
            $dbPrefix = $this->getDbPrefix();
            $result = $db->fetchCol('SHOW TABLES');
            foreach ($result as $tablename) {
                if (strpos($tablename, $dbPrefix) !== false) {
                    $id = str_replace($dbPrefix, '', $tablename);
                    $tables[$id] = $tablename;
                }
            }
        }
        return $tables;
    }

    public function getDbConnection($type = null)
    {
        if (empty($this->dbConnections[$type])) {
            $dbName = $this->getDb();
            $databases = $this->getSite()->getDatabases();
            $db = $databases[$dbName];
            switch ($type) {
                 case 'php':
                    $con = new mysqli($db['host'], $db['user'], $db['password'], $db['name']);
                    break;
                 case 'zend':
                 default:
                    $params = array(
                        'host' => $db['host'], 'username' => $db['user'],
                        'password' => $db['password'], 'dbname' => $db['name']
                    );
                    $con = Zend_Db::factory('Mysqli', $params);
            }
            $this->dbConnections[$type] = $con;
        }
        return $this->dbConnections[$type];
    }

    public function getLinks()
    {
        $links = $this->getManifest()->getLinks();
        foreach ($links as $id => $link) {
            $links[$id]['href'] = $this->getSite()->getBaseUrl() . $this->getPath() . $link['href'];
        }
        return $links;
    }

    public function getPreferences($type = 'preferences')
    {
        $preferences = array();
        
        $prefs = $this->getManifest()->getPreferences($type);
        foreach ($prefs as $pref) {
            $preferences[] = is_object($pref) ? $pref->toArray() : $pref;
        }
        
        return $preferences;
    }

    public function getThemes()
    {
        return $this->getInstaller()->getThemes();
    }

    public function setTheme($theme)
    {
        return $this->getInstaller()->setTheme($theme);
    }
    
    public function getConfiguration($type = 'general')
    {
        return $this->getInstaller()->getConfiguration($type);
    }

    public function setConfiguration($configuration, $type = 'general')
    {
        return $this->getInstaller()->setConfiguration($configuration, $type);
    }

    // Roles

    public function getRoles()
    {
        $installer = $this->getInstaller();
        if (method_exists($installer, 'getRoles')) {
            return $installer->getRoles();
        }
        return array();
    }
        
    public function getUserRoles()
    {
        $installer = $this->getInstaller();
        if (method_exists($installer, 'getUserRoles')) {
            return $installer->getUserRoles();
        }
        return array();
    }

    public function setUserRoles($roles)
    {
        return $this->getInstaller()->setUserRoles($roles);
    }

    // Extensions

    public function getExtensions()
    {
        if (isset($this->extensions)) {
            return $this->extensions;
        }

        $this->getInfos(); // should be temporary

        $extensions = array();
        if (isset($this->infos['extensions']) && count($this->infos['extensions']) > 0) {
            foreach ($this->infos['extensions'] as $key => $extension) {
                try {
                    $instance = new Ld_Instance_Extension($extension);
                    $instance->setParent($this);
                    $extensions[$key] = $instance;
                } catch(Exception $e) {
                    // TODO: log this
                    unset($this->infos['extensions'][$key]);
                }
            }
        }
        return $this->extensions = $extensions;
    }

    public function getExtension($id)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($id == $extension->getPath() || $id == $extension->getPackageId()) {
                 return $extension;
            }
        }
        throw new Exception("Can't find extension with criteria '$id'.");
        return null;
    }

    public function addExtension($extension, $preferences = array())
    {
        $package = $this->site->getPackageExtension($this->getPackageId(), $extension);

        $this->getInstaller(); // include the application installer
        $installer = $package->getInstaller();

        foreach ($package->getManifest()->getDependencies() as $dependency) {
            if (null === $this->getSite()->_getLibraryInfos($dependency)) {
                $this->getSite()->createInstance($dependency);
            }
        }

        $extendedPath = $package->getManifest()->getDirectory();
        if (empty($extendedPath)) {
            throw new Exception('extendedPath not defined.');
        } else {
            $installer->setPath( $this->getPath() . '/' . $extendedPath );
            $installer->setAbsolutePath( $this->getAbsolutePath() . '/' . $extendedPath );
        }

        $installer->install($preferences);

        // Register

        $params = array(
            'package'   => $package->getId(),
            'type'      => $package->getType(),
            'version'   => $package->getVersion(),
            'path'      => $extendedPath
        );

        $this->registerExtension($params);

        // TODO: would be better to return an object instead
        return true;
    }
        
    public function updateExtension($extension)
    {
        if (is_string($extension)) {
            $extension = $this->getExtension($extension);
        }

        $package = $this->site->getPackageExtension($this->getPackageId(), $extension->package);

        // Get Installer
        $this->getInstaller(); // include the application installer
        $installer = $package->getInstaller();

        // Update
        $installer->setPath( $this->getPath() . '/' . $extension->getPath() );
        $installer->setAbsolutePath( $this->getAbsolutePath() . '/' . $extension->getPath() );
        $installer->update();
        
        // Update registry
        // $extension->setInfos(array('version' => $package->version))->save();
        
        // We update application registry instead
        foreach ($this->infos['extensions'] as $key => $infos) {
            if ($infos['path'] == $extension->getPath()) {
                $this->infos['extensions'][$key]['version'] = $package->version;
                $this->save();
                break;
            }
        }
    }

    public function removeExtension($extension)
    {
        if (is_string($extension)) {
            $extension = $this->getExtension($extension);
        }

        // Get Installer
        $this->getInstaller(); // include the application installer
        $installer = $extension->getInstaller();

        // Uninstall
        $installer->uninstall();

        // Unregister
        $this->unregisterExtension( $extension->getPath() );

        return true;
    }

    public function registerExtension($params)
    {
        if (empty($this->infos['extensions'])) {
            $this->infos['extensions'] = array();
        }
        // $key = $params['path'];
        $this->infos['extensions'][] = $params;
        $this->save();
    }

    public function unregisterExtension($path)
    {
        $this->getInfos(); // should be temporary

        foreach ($this->infos['extensions'] as $key => $extension) {
            if ($extension['path'] == $path) {
                $found = true;
                unset($this->infos['extensions'][$key]);
            }
        }
        if (isset($found) && $found) {
            $this->save();
        }
    }

    // Backups

    public function doBackup()
    {
        return $this->getInstaller()->backup();
    }

    public function restoreBackup($archive, $absolute = false)
    {
        return $this->getInstaller()->restore($archive, $absolute);
    }

    public function getBackups()
    {
        $archives = array();
        if (file_exists($this->getAbsolutePath() . '/backups/')) {
            $archives = Ld_Files::getFiles($this->getAbsolutePath() . '/backups/');
        }
        return $archives;
    }

    public function deleteBackup($backup)
    {
        $filename = $this->getAbsolutePath() . '/backups/' . $backup;
        if (file_exists($filename)) {
            Ld_Files::unlink($filename);
        }
    }

}
