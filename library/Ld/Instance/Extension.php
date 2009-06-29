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
