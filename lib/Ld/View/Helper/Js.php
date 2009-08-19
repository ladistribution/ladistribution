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

class Ld_View_Helper_Js extends Zend_View_Helper_Abstract
{

    public function getSite()
    {
        return Zend_Registry::get('site');
    }

    public function js()
    {
        return $this;
    }

    public function append($file, $package)
    {
        $infos = $this->getSite()->getLibraryInfos($package);
        if ($infos['type'] == 'application') {
            $infos = $this->getSite()->getInstance($infos['path'])->getInfos();
        }
        $url = $this->getSite()->getUrl('js') . $file . '?v=' . $infos['version'];
        $this->view->headScript()->offsetSetFile($package, $url);
        return $this;
    }

}