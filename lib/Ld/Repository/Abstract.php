<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Repository
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

abstract class Ld_Repository_Abstract
{

    public $id = null;

    public $name = null;

    public $type = null;

    protected $types = array(
        'applications'  => array('application', 'bundle'),
        'libraries'     => array('shared', 'lib', 'css', 'js'),
        'extensions'    => array('theme', 'plugin', 'locale')
    );

    abstract public function getUrl();

    abstract public function getPackages();

    abstract public function getApplications();

    abstract public function getLibraries();

    abstract public function getExtensions();

    abstract public function getPackageExtensions($packageId, $type = null);

}
