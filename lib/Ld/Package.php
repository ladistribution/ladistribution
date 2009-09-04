<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Package
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Package
{

    public $id = null;

    public $name = null;

    public $type = null;

    public $version = null;

    public $extend = null;

    public $url = null;
    
    protected $absoluteFilename = null;

    protected $_manifest = null;

    protected $_installer = null;

    public function __construct($params = array())
    {
        if ($params instanceof Ld_Manifest) {
            $this->_manifest = $params;
            $params = $this->_manifest->getInfos();
        }

        $this->setParams($params);
    }

    public function setParams($params = array())
    {
        $infos = array('id', 'name', 'type', 'version', 'extend', 'url');
        foreach ($infos as $key) {
            if (isset($params[$key])) {
                $this->$key = $params[$key];
            }
        }
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        if (isset($this->site)) {
            return $this->site;
        }
        return Zend_Registry::get('site');
    }

    public function setAbsoluteFilename($filename)
    {
        $this->absoluteFilename = $filename;
    }

    public function getArchive()
    {
        if (isset($this->absoluteFilename)) {
            return $this->absoluteFilename;
        }

        $filename = LD_TMP_DIR . '/' . $this->id . '-' . $this->version . '.zip';
        if (!file_exists($filename)) {
            $httpClient = new Zend_Http_Client($this->url);
            $response = $httpClient->request();
            if ($response->isError()) {
                $message = 'HTTP Error with ' . $this->url . ' - ' . $response->getStatus() . ' : ' . $response->getMessage();
                throw new Exception($message);
            }
            $zip = $response->getBody();
            Ld_Files::put($filename, $zip);
        }
        return $filename;
    }

    public function getTmpDir()
    {
        return LD_TMP_DIR . '/' . $this->id . '-' . $this->version . '/';
    }

    public function fetchFiles()
    {
        $dir = $this->getTmpDir();
        if (!file_exists($dir)) {
            Ld_Files::purgeTmpDir();
            $archive = $this->getArchive();
            $uz = new fileUnzip($archive);
            $uz->unzipAll($dir);
        }
        return $dir;
    }

    public function getManifest()
    {
        if (empty($this->_manifest)) {
            $dir = $this->fetchFiles();
            $this->_manifest = Ld_Manifest::loadFromDirectory($dir);
        }
        return $this->_manifest;
    }

    public function getInstaller()
    {
        if (empty($this->_installer)) {
            $dir = $this->fetchFiles();
            $classFile = $dir . 'dist/installer.php';
            $className = $this->getManifest()->getClassName();
            if (!file_exists($classFile)) {
                $className = 'Ld_Installer';
            } else {
                if (!class_exists($className, false)) {
                    require_once($classFile);
                }
            }
            $this->_installer = new $className(array('package' => $this));
        }
        return $this->_installer;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getInstallPreferences()
    {
        $preferences = array();

        if ($this->type == 'application' || $this->type == 'bundle') {
            $preferences[] = array('type' => 'text', 'name' => 'title',
                    'label' => 'Title', 'defaultValue' => $this->name);
            $preferences[] = array('type' => 'text', 'name' => 'path',
                    'label' => 'Path', 'defaultValue' => $this->id);
        }

        $prefs = $this->getManifest()->getPreferences('install');
        foreach ($prefs as $pref) {
            $preferences[] = is_object($pref) ? $pref->toArray() : $pref;
        }

        return $preferences;
    }

    // Legacy

    public function getPreferences($type = 'configuration')
    {
        return $this->getManifest()->getPreferences($type);
    }

}
