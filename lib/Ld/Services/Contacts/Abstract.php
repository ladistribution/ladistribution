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
        if (empty($_cache)) {
            $options = array('servers' => array(array('host' => 'natty', 'timeout' => 1, 'readTimeout' => 1)));
            $this->_cache = new Rediska($options);
        }
        return $this->_cache;
    }

    public function getValue($key)
    {
        $key = new Rediska_Key($key);
        return $key->getValue();
    }

    public function setValue($key, $value)
    {
        $key = new Rediska_Key($key);
        return $key->setValue($value);
    }

}
