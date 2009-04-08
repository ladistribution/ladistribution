<?php

abstract class Ld_Instance_Abstract
{

    public $package;

    public $type;

    public $path;

    public $version;

    protected $infos = array();

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

}