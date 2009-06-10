<?php

class Ld_Instance_Extension extends Ld_Instance_Abstract
{

    protected $_parent;

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getAbsolutePath()
    {
        return $this->getParent()->getAbsolutePath() . '/' . $this->path;
    }

    public function getParent()
    {
        return $this->_parent;
    }

    public function setParent($parent)
    {
        $this->_parent = $parent;
    }

    public function getPackage()
    {
        $site = $this->getParent()->getSite();
        return $site->getPackageExtension($this->getParent()->getPackageId(), $this->getPackageId());
    }

}
