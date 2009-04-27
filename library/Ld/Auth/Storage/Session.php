<?php

require_once 'Zend/Auth/Storage/Session.php';

class Ld_Auth_Storage_Session extends Zend_Auth_Storage_Session
{

    protected $_duration;

    public function setDurationSeconds($sec)
    {
        $this->_duration = $sec;
        $this->_session->setExpirationSeconds($this->_duration);
    }

    public function read()
    {
        if (isset($this->_duration)) {
            $this->_session->setExpirationSeconds($this->_duration);
        }
        return parent::read();
    }

    public function isEmpty()
    {
        if (isset($this->_duration)) {
            $this->_session->setExpirationSeconds($this->_duration);
        }
        return parent::isEmpty();
    }

}
