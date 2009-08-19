<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_View
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_View_Helper_Css extends Zend_View_Helper_Abstract
{

    public function getSite()
    {
        return Zend_Registry::get('site');
    }

    public function css()
    {
        return $this;
    }

    public function append($file, $package, $t = 'screen')
    {
        $infos = $this->getSite()->getLibraryInfos("css-$package");
        $url = $this->getSite()->getUrl('css') . $file . '?v=' . $infos['version'];
        $this->view->headLink()->appendStylesheet($url, $t);
        return $this;
    }

}