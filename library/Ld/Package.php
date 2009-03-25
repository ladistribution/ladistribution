<?php

class Ld_Package
{

    public $id = null;

    public $name = null;

    public $type = null;

    public $version = null;

    public $extend = null;

    protected $manifest = null;

    protected $manifestXml = null;

    public function __construct($params = array())
    {

        if (isset($params['zip'])) {
            // unzip
            $tmpFolder = LD_TMP_DIR . '/package-' . date("d-m-Y-H-i-s");
            $uz = new fileUnzip($params['zip']);
            $uz->unzipAll($tmpFolder);

            // parse manifest
            $filename = $tmpFolder . '/dist/manifest.xml';
            if (!file_exists($filename)) {
                $filename = $tmpFolder . '/manifest.xml'; // alternate name
            }
            if (file_exists($filename)) {
                $this->manifestXml = file_get_contents($filename);
            } else {
                throw new Exception("manifest.xml doesn't exists or is unreadable in $tmpFolder");
            }
            // unlink temporary folder
            Ld_Files::unlink($tmpFolder);
        }

        if (isset($params['manifest'])) {
            $this->manifestXml = file_get_contents($params['manifest']);
        }

        if (isset($this->manifestXml)) {
            $this->manifest = new SimpleXMLElement($this->manifestXml);
            $this->id = (string)$this->manifest->id;
            $this->version = (string)$this->manifest->version;
            $this->type = (string)$this->manifest->type;
            $this->name = (string)$this->manifest->name;
            if (in_array($this->type, array('theme', 'plugin'))) {
                $this->extend = (string)$this->manifest->extend;
            }
        }

    }

    public function setInfos($params = array())
    {
        $infos = array('id', 'name', 'type', 'version', 'extend', 'url');
        foreach ($infos as $key) {
            if (isset($params[$key])) {
                $this->$key = $params[$key];
            }
        }
    }

    public function getManifestXml()
    {
        if (isset($this->manifestXml)) {
            return $this->manifestXml;
        }
    }

    public function getManifest()
    {
        if (isset($this->manifest)) {
            return $this->manifest;
        }
    }

}