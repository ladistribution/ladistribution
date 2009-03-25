<?php

class Ld_Instance
{

    public $infos = array();

    public $update = false;

    public function __construct($path = null)
    {
        if (isset($path)) {
            $this->setPath($path);
            if (file_exists($this->absolutePath) && file_exists($this->instanceJson)) {
                $this->getInfos();
            }
        }
    }

    public function __get($value)
    {
        if (empty($this->infos)) {
            $this->infos = $this->getInfos();
        }
        if (isset($this->infos[$value])) {
            return $this->infos[$value];
        }
        return null;
    }

    // 
    // public function getPath()
    // {
    //     return $this->path;
    // }
    // 

    public function getInfos()
    {
        if (!file_exists($this->absolutePath)) {
            throw new Exception("no application found in path $this->absolutePath");
        }

        if (!file_exists($this->instanceJson)) {
            throw new Exception("instance.json not found in path $this->absolutePath");
        }

        $json = file_get_contents($this->instanceJson);

        $this->infos = Zend_json::decode($json);

        foreach ($this->infos as $key => $value) {
            $this->$key = $value;
        }

        return $this->infos;
    }

    // public function setSite($site)
    // {
    //     $this->site = $site;
    // }

    // public function hasUpdate()
    // {
    //     if (isset($this->site)) {
    //         $package = $this->site->getPackage($this->package);
    //         return $this->version != $package->version;
    //     }
    //     echo "Can't read update infos.";
    //     return false;
    // }
    
    public function setInfos($infos = array())
    {
       $this->infos = array_merge($this->infos, $infos);
       return $this;
    }

    public function save()
    {
        $json = Zend_Json::encode($this->infos);
        file_put_contents($this->instanceJson, $json);
    }

}