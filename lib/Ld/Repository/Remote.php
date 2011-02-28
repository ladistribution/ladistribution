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

    public $type = 'remote';

    public $endpoint = null;

    public function __construct($params = array())
    {
        if (is_array($params)) {
            $keys = array('id', 'type', 'name', 'endpoint', 'locked');
            foreach ($keys as $key) {
                if (isset($params[$key])) {
                    $this->$key = $params[$key];
                }
            }
        }

        $this->getPackages();
    }

    public function getCacheKey()
    {
        return 'Ld_Repository_Remote_Packages_' . md5($this->endpoint);
    }

    public function getPackagesJson()
    {
        try {
            $json = Ld_Http::get($this->endpoint . '/packages.json');
            if ($json) {
               return Zend_Json::decode($json);
            }
        } catch (Exception $e) {
            // output warning in debug mode ?
        }
        return array();
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
