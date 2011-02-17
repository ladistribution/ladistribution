<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Package
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Package
{

    public $id = null;

    public $name = null;

    public $description = null;

    public $type = null;

    public $version = null;

    public $extend = null;

    public $url = null;

    public $size = null;

    public $sha1 = null;

    public $icon = null;

    protected $absoluteFilename = null;

    protected $_manifest = null;

    protected $_installer = null;

    protected $_dir = null;

    public function __construct($params = array())
    {
        if ($params instanceof Ld_Manifest) {
            $this->_manifest = $params;
            $params = $this->_manifest->getInfos();
        }

        $this->setParams($params);
    }

    public static function loadFromDirectory($dir)
    {
        $manifest = Ld_Manifest::loadFromDirectory($dir);
        $package = new Ld_Package($manifest);
        $package->setDir($dir);
        return $package;
    }

    public function setParams($params = array())
    {
        $infos = array('id', 'name', 'description', 'type', 'version', 'extend', 'url', 'sha1', 'size', 'icon');
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

    public function setDir($dir)
    {
        $this->_dir = $dir;
    }

    public function getDir()
    {
        if (isset($this->_dir)) {
            return $this->_dir;
        }

        $dir = LD_TMP_DIR . '/' . $this->id . '-' . $this->version . '/';
        if (!Ld_Files::exists($dir)) {
            Ld_Files::purgeTmpDir();
            Ld_Files::createDirIfNotExists($dir);
            $archive = $this->getArchive();
            Ld_Zip::extract($archive, $dir);
        }
        return $dir;
    }

    public function fetchFiles()
    {
        return $this->getDir();
    }

    public function getManifest()
    {
        if (empty($this->_manifest)) {
            $dir = $this->getDir();
            $this->_manifest = Ld_Manifest::loadFromDirectory($dir);
        }
        return $this->_manifest;
    }

    public function getInstaller($forceNew = false)
    {
        if (empty($this->_installer) || $forceNew) {
            $dir = $this->getDir();
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

    public function getDescription()
    {
        return $this->description;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getInstallPreferences()
    {
        $preferences = array();

        $prefs = $this->getManifest()->getPreferences('install');
        foreach ($prefs as $pref) {
            $pref = is_object($pref) ? $pref->toArray() : $pref;
            $name = $pref['name'];
            $preferences[$name] = $pref;
        }

        if ($this->type == 'application' || $this->type == 'bundle') {
            if (empty($preferences['path'])) {
                $path = array('type' => 'text', 'name' => 'path', 'label' => 'Path', 'defaultValue' => $this->id);
                array_unshift($preferences, $path);
            }
            if (empty($preferences['title'])) {
                $title = array('type' => 'text', 'name' => 'title', 'label' => 'Title', 'defaultValue' => $this->name);
                array_unshift($preferences, $title);
            }
        }

        return $preferences;
    }

    public function getRawIcon($type = 'ld-icon')
    {
        foreach ($this->getManifest()->getLinks() as $link) {
              if ($link['rel'] == $type) {
                  // this does the job but it's not ideal
                  $filename = $this->getDir() . '/application' . $link['href'];
                  return Ld_Files::get($filename);
              }
        }
    }

    // Legacy

    public function getPreferences($type = 'configuration')
    {
        return $this->getManifest()->getPreferences($type);
    }

    public function getTmpDir()
    {
        return $this->getDir();
    }

}
