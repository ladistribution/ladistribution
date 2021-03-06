<?php

require_once(DOKU_INC.'/inc/auth/plain.class.php');

class auth_ld extends auth_plain
{

    /**
     * Constructor
     */
    function auth_ld()
    {
        $this->cando['addUser']      = true;

        $this->cando['getUsers']     = true;
        $this->cando['getUserCount'] = true;

        $this->cando['external'] = true;
        $this->cando['logoff'] = true;

        if (Ld_Auth::isAuthenticated()) {
            $_REQUEST['u'] = Ld_Auth::getUsername();
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

      $roles = Ld_Files::getJson(DOKU_INC . '/dist/roles.json');

      // Default role for authenticated user
      if (Ld_Auth::isAuthenticated()) {
          $username = Ld_Auth::getUsername();
          if (empty($roles[$username])) {
              $roles[$username] = 'user';
          }
      }

      foreach ($roles as $username => $role) {
          $user = Zend_Registry::get('site')->getUser($username);
          $id = $user['username'];
          if ($role == 'admin') {
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

        $result = Ld_Auth::authenticate($user, $pass);
        if ($result->isValid()) {
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
        Ld_Auth::logout();
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
        // if a valid Zend_Auth session is currently active
        if (Ld_Auth::isAuthenticated() && $user == Ld_Auth::getUsername()) {
            $this->logIn($user);
            return true;

        // in case user and password are provided
        } else if ($this->checkPass($user,$pass)) {
            $this->logIn($user);
            $this->handle_ld_session();
            return true;
        }

        // fallback to dokuwiki authentication
        auth_login($_REQUEST['u'], $_REQUEST['p']);
        $this->handle_ld_session();
    }

    function handle_ld_session()
    {
        if (!Ld_Auth::isAuthenticated()) {
            $auth = Zend_Auth::getInstance();
            $adapter = new Ld_Auth_Adapter_Dokuwiki();
            $auth->authenticate($adapter);
        }
    }

    function createUser($user,$pwd,$name,$mail,$grps=null)
    {
        $user = array(
            'origin'    => 'Dokuwiki:createUser',
            'username'  => $user,
            'password'  => $pwd,
            'fullname'  => $name,
            'email'     => $mail
        );
        Zend_Registry::get('site')->addUser($user, false);
        return true;
    }

}

class Ld_Auth_Adapter_Dokuwiki implements Zend_Auth_Adapter_Interface
{
    public function authenticate()
    {
        if (isset($_SERVER['REMOTE_USER'])) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $_SERVER['REMOTE_USER']);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
    }
}
