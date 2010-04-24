<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Repository
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Repository_Remote extends Ld_Repository_Abstract
{

    public $endpoint = null;

    public function __construct($params = array())
    {
        if (is_array($params)) {
            $this->id = $params['id'];
            $this->type = $params['type'];
            $this->name = $params['name'];
            $this->endpoint = $params['endpoint'];
        }

        $this->getPackages();
    }

    public function getCacheKey()
    {
        return 'Ld_Repository_Remote_Packages_' . md5($this->endpoint);
    }

    public function getPackagesJson()
    {
        $json = Ld_Http::get($this->endpoint . '/packages.json');
        if (!$json) {
            return array();
        }
        return Zend_Json::decode($json);
    }

    public function getUrl()
    {
        return $this->endpoint;
    }

    public function getPackage($params = array())
    {
        $package = new Ld_Package($params);
        return $package;
    }

}
