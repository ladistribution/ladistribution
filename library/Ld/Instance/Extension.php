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
        return $this->_parent->getAbsolutePath() . '/' . $this->path;
    }
    
    public function setParent($parent)
    {
        $this->_parent = $parent;
    }

}
