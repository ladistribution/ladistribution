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

    protected $absolutePath;

    protected $instanceJson;
    
    public function __construct($params = array())
    {
        if (is_string($params) && is_dir($params) && file_exists($params . '/dist/instance.json')) {
            require_once 'Zend/Json.php';
            $params = Zend_Json::decode( file_get_contents($params . '/dist/instance.json') );
        }
        
        parent::__construct($params);
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
        $json = Zend_Json::encode($this->infos);
        Ld_Files::put($this->getInstanceJson(), $json);
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

    public function getDbConnection()
    {
        $dbName = $this->getDb();
        $databases = $this->getSite()->getDatabases();
        $db = $databases[$dbName];
        $con = Zend_Db::factory('Mysqli', array(
            'host'     => $db['host'],
            'username' => $db['user'],
            'password' => $db['password'],
            'dbname'   => $db['name']
        ));
        return $con;
    }

    /* From Installer */

    public function getInstaller()
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $this, 'dbPrefix' => $this->getDbPrefix()));
        $installer->setSite($this->site);
        return $installer;
    }

    public function getLinks()
    {
        $manifest = $this->getInstaller()->getManifest();
        $links = array();
        foreach ($manifest->link as $link) {
            $title = (string)$link['title'];
            $rel = (string)$link['rel'];
            $type = (string)$link['type'];
            $href = $this->site->getBaseUrl() . $this->getPath() . $link['href'];
            $links[] = compact('title', 'rel', 'href', 'type');
        }
        return $links;
    }

    public function getPreferences($type = 'preferences')
    {
        $preferences = array();
        
        $prefs = $this->getInstaller()->getPreferences($type);
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

        // Install

        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));

        foreach ($installer->getDependencies() as $dependency) {
            if (null === $this->site->_getLibraryInfos($dependency)) {
                $this->site->createInstance($dependency);
            }
        }

        $extendedPath = $installer->getExtendedPath();
        if (empty($extendedPath)) {
            throw new Exception('Extended path not defined.');
        } else {
            $installer->setPath( $this->getPath() . '/' . $extendedPath );
            $installer->setAbsolutePath( $this->getAbsolutePath() . '/' . $extendedPath );
        }

        $installer->install($preferences);

        // Register

        $params = array(
            'package'   => $package->id,
            'type'      => $package->type,
            'version'   => $package->version,
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
        
        // Update
        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));
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

        // Uninstall
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $extension));
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
