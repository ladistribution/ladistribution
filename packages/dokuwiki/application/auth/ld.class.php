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
    }

    /**
     * Load all user data
     *
     * loads the user file into a datastructure
     */
    function _loadUserData()
    {
      parent::_loadUserData();
      
      $site = Zend_Registry::get('site');
      $users = $site->getUsers();

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

}
