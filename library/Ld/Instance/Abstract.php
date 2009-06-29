<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Instance
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
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

    public $site;

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
       
       // temporary
       foreach (array('package', 'name', 'path', 'type', 'version', 'url', 'db', 'db_prefix') as $key) {
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

}
