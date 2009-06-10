<?php

require_once 'Ld/Instance/Abstract.php';

abstract class Ld_Instance_Application_Abstract extends Ld_Instance_Abstract
{

    public $name;

    protected $site;

    public function getName()
    {
        $infos = $this->getInfos();
        return $infos['name'];
    }

    public function getUrl()
    {
        $infos = $this->getInfos();
        return $infos['url'];
    }

    abstract public function getLinks();

    abstract public function getPreferences($type = 'preferences');

    abstract public function getConfiguration($type = 'general');

    abstract public function setConfiguration($configuration, $type = 'general');

    abstract public function getExtensions();

    abstract public function addExtension($extension, $preferences = array());

    abstract public function updateExtension($extension);

    abstract public function removeExtension($extension);

    abstract public function getThemes();

    abstract public function setTheme($theme);

}
