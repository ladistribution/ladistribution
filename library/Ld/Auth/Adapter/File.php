<?php

class Ld_Auth_Adapter_File implements Zend_Auth_Adapter_Interface
{

    protected $_username = null;

    protected $_password = null;

    public function setCredentials($username, $password)
    {
        $this->_username = $username;
        $this->_password = $password;
    }

    public function authenticate()
    {
        $users = Ld_Auth::getUsers();
        if (empty($users)) {
            throw new Exception("No user defined."); 
        }
        foreach ($users as $user) {
            if ($this->_username == $user['username'] && sha1($this->_password) == $user['hash']) {
                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->_username);
            }
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
    }

}
