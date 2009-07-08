<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Installer
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Installer_Bundle extends Ld_Installer
{

    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->application = (string)$this->getManifest()->xml->application;

        $this->extensions = array();
        foreach ($this->getManifest()->xml->extension as $extension) {
            $this->extensions[] = (string)$extension;
        }
    }

}
