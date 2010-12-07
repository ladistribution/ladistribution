<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Package
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
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

    public $size = null;

    public $sha1 = null;

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
        $infos = array('id', 'name', 'type', 'version', 'extend', 'url', 'sha1', 'size');
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

        $attemps = 3;
        while ($attemps > 0) {
            if (!Ld_Files::exists($filename)) {
                Ld_Http::download($this->url, $filename);
            }
            if (Ld_Files::check($filename, $this->size, $this->sha1)) {
                return $filename;
            }
            Ld_Files::rm($filename);
            usleep(rand(0, 1000000));
            $attemps --;
        }

        throw new Exception("Can't retrieve archive $this->url");
    }

    public function getTmpDir()
    {
        return LD_TMP_DIR . '/' . $this->id . '-' . $this->version . '/';
    }

    public function fetchFiles()
    {
        $dir = $this->getTmpDir();
        if (!Ld_Files::exists($dir)) {
            Ld_Files::purgeTmpDir();
            Ld_Files::createDirIfNotExists($dir);
            $archive = $this->getArchive();
            Ld_Zip::extract($archive, $dir);
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
            if (!Ld_Files::exists($classFile)) {
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
            $preferences[] = array('type' => 'text', 'name' => 'title', 'label' => 'Title', 'defaultValue' => $this->name);
            $preferences[] = array('type' => 'text', 'name' => 'path', 'label' => 'Path', 'defaultValue' => $this->id);
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
