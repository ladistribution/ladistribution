<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Instance
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

abstract class Ld_Instance_Abstract
{

    public $package;

    public $type;

    public $path;

    public $version;

    protected $infos = array();

    protected $site;

    public function __construct($infos = null)
    {
        if (isset($infos) && is_array($infos)) {
            $this->setInfos($infos);
        }
    }

    /* Simple Getters */

    public function getPackageId()
    {
        $infos = $this->getInfos();
        return $infos['package'];
    }

    public function getPath()
    {
        $infos = $this->getInfos();
        return $infos['path'];
    }

    public function getType()
    {
        $infos = $this->getInfos();
        return $infos['type'];
    }

    public function getVersion()
    {
        $infos = $this->getInfos();
        return $infos['version'];
    }

    public function getInfos()
    {
        return $this->infos;
    }

    /* Setters */

    public function setInfos($infos = array())
    {
       $this->infos = array_merge($this->infos, $infos);

       if ($this->infos['package'] == 'weave') {
           $this->infos['package'] = 'firefox-sync';
       }

       // temporary
       foreach (array('package', 'name', 'path', 'type', 'version', 'url', 'db', 'db_prefix', 'domain') as $key) {
           if (isset($this->infos[$key])) {
               $this->$key = $this->infos[$key];
           }
       }

       return $this;
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

    public function getPackage()
    {
        return $this->getSite()->getPackage( $this->getPackageId() );
    }

    public function hasUpdate()
    {
        try {
            $package = $this->getPackage();
        } catch (Exception $e) {
            $package = null;
        }
        if ($package) {
            return version_compare($package->version, $this->getVersion(), '>') ? $package->version : false;
        }
        // package is unknown, it can only be 'up to date' then
        return false;
    }

    public function getInstaller()
    {
        if (empty($this->_installer)) {
            $classFile = $this->getAbsolutePath() . '/dist/installer.php';
            $className = $this->getManifest()->getClassName();
            if (!file_exists($classFile)) {
                $className = 'Ld_Installer';
            } else {
                if (!class_exists($className, false)) {
                    require_once($classFile);
                }
            }
            $this->_installer = new $className(array('instance' => $this));
        }
        return $this->_installer;
    }

    public function getManifest()
    {
        if (empty($this->_manifest)) {
            $this->_manifest = Ld_Manifest::loadFromDirectory($this->getAbsolutePath());
        }
        return $this->_manifest;
    }

}
