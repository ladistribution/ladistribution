<?php

define('DOKU_AUTH', dirname(__FILE__));
require_once(DOKU_AUTH.'/plain.class.php');

class auth_ld extends auth_plain
{

    /**
     * Constructor
     */    
    function auth_ld()
    {
        $this->cando['getUsers']     = true;
        $this->cando['getUserCount'] = true;

        $this->cando['external'] = true;
        $this->cando['logoff'] = true;

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $_REQUEST['u'] = $auth->getIdentity();
        }
    }

    /**
     * Load all user data
     *
     * loads the user file into a datastructure
     */
    function _loadUserData()
    {
      parent::_loadUserData();

      $users = Zend_Registry::get('site')->getUsers();

      if (file_exists(DOKU_INC . '/dist/roles.json')) {
          $json = file_get_contents(DOKU_INC . '/dist/roles.json');
          require_once 'Zend/Json.php';
          $roles = Zend_Json::decode($json);
      }

      foreach ($users as $user) {
          $id = $user['username'];
          if (isset($roles[$id]) && $roles[$id] == 'admin') {
              $grps = array('admin', 'user');
          } else {
              $grps = array('user');
          }
          $this->users[$id]['pass'] = $user['hash'];
          $this->users[$id]['name'] = $user['fullname'];
          $this->users[$id]['mail'] = $user['email'];
          $this->users[$id]['grps'] = $grps;
          $this->users[$id]['openids'] = $user['identities'];
      }

    }

    /**
     * Check user+password
     *
     * @return  bool
     */
    function checkPass($user, $pass)
    {
        $userinfo = $this->getUserData($user);
        if ($userinfo === false) return false;

        if ($pass == $this->users[$user]['pass']) {
            return true;
        }

        $hasher = new Ld_Auth_Hasher(8, TRUE);
        if ($hasher->CheckPassword($pass, $this->users[$user]['pass'])) {
            return true;
        }

        return auth_verifyPassword($pass,$this->users[$user]['pass']);
    }

    /**
     * Log off the current user
     *
     * Is run in addition to the ususal logoff method. Should
     * only be needed when trustExternal is implemented.
     */    
    function logOff()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $auth->clearIdentity();
        }
    }
    
    function logIn($user)
    {
        global $USERINFO;

        $USERINFO = $this->getUserData($user);
        if ($USERINFO === false) return false;

        $_SERVER['REMOTE_USER'] = $user;
        $_SESSION[DOKU_COOKIE]['auth']['user'] = $user;
        $_SESSION[DOKU_COOKIE]['auth']['pass'] = 'XXXX';
        $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;

        return true;
    }

    function trustExternal($user,$pass,$sticky=false)
    {
        global $USERINFO;

        $userinfo = $this->getUserData($user);
        if ($userinfo === false) return false;

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity() && $user == $auth->getIdentity()) {

            $this->logIn($user);

            return true;

        } else if ($this->checkPass($user,$pass)) {

            $this->logIn($user);

            $auth = Zend_Auth::getInstance();
            if (!$auth->hasIdentity()) {
                $adapter = new Ld_Auth_Adapter_File_Dokuwiki();
                $auth->authenticate($adapter);
            }

            return true;
        }
    }

}

class Ld_Auth_Adapter_File_Dokuwiki implements Zend_Auth_Adapter_Interface
{
    public function authenticate()
    {
        if (isset($_SERVER['REMOTE_USER'])) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $_SERVER['REMOTE_USER']);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
    }
}
