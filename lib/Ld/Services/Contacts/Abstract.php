<?php

abstract class Ld_Services_Contacts_Abstract
{

    public function __construct($params = array())
    {
        if (isset($params['service'])) {
            $this->setService($params['service']);
        }
    }

    public function getService()
    {
        return $this->_service;
    }

    public function setService($service)
    {
        $this->_service = $service;
    }

    public function request($url, $method = 'GET', $params = array())
    {
        return $this->getService()->request($url, $method, $params);
    }

    protected $_cache;

    public function getCache()
    {
        // if (empty($_cache)) {
        //     $options = array('servers' => array(array('host' => 'natty', 'timeout' => 1, 'readTimeout' => 1)));
        //     $this->_cache = new Rediska($options);
        // }
        // return $this->_cache;
    }

    public function getValue($key)
    {
        $this->getCache();
        $key = new Rediska_Key($key);
        return $key->getValue();
    }

    public function setValue($key, $value)
    {
        $this->getCache();
        $key = new Rediska_Key($key);
        return $key->setAndExpire($value, 300 /* seconds */);
    }

    public function getRawUser()
    {
        return $this->getService()->getRawUser();
    }

}
