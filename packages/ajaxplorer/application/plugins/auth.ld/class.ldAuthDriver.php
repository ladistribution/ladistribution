<?php

defined('AJXP_EXEC') or die( 'Access not allowed');

require_once(INSTALL_PATH."/server/classes/class.AbstractAuthDriver.php");

class ldAuthDriver extends AbstractAuthDriver {

    var $driverName = "ld";
    var $driverType = "auth";

    var $autologin;

    function init($options){
        parent::init($options);
    }

    function autologin(){
        $this->autologin = true;
        if (Ld_Auth::isAuthenticated()) {
            $username = Ld_Auth::getUsername();
            $confDriver = ConfService::getConfStorageImpl();
            $user = $confDriver->createUserObject($username);
            // may be a performance issue there
            if($user->isAdmin()) {
                $user = AuthService::updateAdminRights($user);
            }
            $_SESSION["AJXP_USER"] = $user;
        } else {
            unset($_SESSION["AJXP_USER"]);
        }
    }

    function listUsers(){
        if ($this->autologin == false) {
            $this->autologin();
        }

        $users = array();
        $ldUsers = Zend_Registry::get('site')->getUsers();
        foreach ($ldUsers as $id => $user) {
            $login = $user['username'];
            $password = $user['hash'];
            $users[$login] = $password;
        }
        return $users;
    }

    function userExists($login){
        $users = $this->listUsers();
        if(!is_array($users) || !array_key_exists($login, $users)) return false;
        return true;
    }

    function checkPassword($login, $pass, $seed){
        $result = Ld_Auth::authenticate($login, $pass);
        if ($result->isValid()) {
            return true;
        }
        return false;
    }


    function createUser($login, $passwd){
        $users = $this->listUsers();
        if(!is_array($users)) $users = array();
        if(array_key_exists($login, $users)) return "exists";
        $user = array('username' => $login, 'password' => $passwd);
        Zend_Registry::get('site')->addUser($user, false);
    }

    function changePassword($login, $newPass){
        $users = $this->listUsers();
        if(!is_array($users) || !array_key_exists($login, $users)) return;
        $user = array('password' => $newPass);
        Zend_Registry::get('site')->updateUser($login, $user);
    }

    function deleteUser($login){
        $users = $this->listUsers();
        if(is_array($users) && array_key_exists($login, $users)){
            Zend_Registry::get('site')->deleteUser($login);
        }
    }

    function usersEditable(){
        return false;
    }

    function passwordsEditable(){
        return false;
    }

}
