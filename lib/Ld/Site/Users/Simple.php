<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Site
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Site_Users_Simple
{

    protected $_site = null;

    protected $_users = array();

    public function setSite($site)
    {
        return $this->_site = $site;
    }

    public function getSite()
    {
        return $this->_site;
    }

    public function getUsers($params = array())
    {
        $users = $this->_users;

        if (empty($users)) {
            $users = Ld_Files::getJson($this->getSite()->getDirectory('dist') . '/users.json');
        }

        foreach ((array)$users as $key => $user) {
            $users[$key]['id'] = $key;
            if (isset($params['query'])) {
                $q = $params['query'];
                if (strpos($user['username'], $q) === false && strpos($user['email'], $q) === false) {
                    unset($users[$key]);
                }
            }
        }

        return $this->_users = $users;
    }

    public function getUserBy($key = 'username', $value)
    {
        foreach (self::getUsers() as $id => $user) {
            if (!empty($user[$key]) && $user[$key] == $value) {
                return $user;
            }
        }
        return null;
    }

    public function getUser($username)
    {
        return $this->getUserBy('username', $username);
    }

    public function getUserByEmail($email)
    {
        return $this->getUserBy('email', $email);
    }

    public function getUserByUrl($url)
    {
        foreach (self::getUsers() as $id => $user) {
            if (!empty($user['identities'])) {
                foreach ($user['identities'] as $identity) {
                    if ($identity == $url) {
                        return $user;
                    }
                }
            }
        }
        return null;
    }

    public function addUser($user, $validate = true)
    {
        if ($validate) {
            $this->validateUser($user);
        }

        $hasher = new Ld_Auth_Hasher(8, TRUE);

        if (isset($user['password'])) {
            $user['hash'] = $hasher->HashPassword($user['password']);
            unset($user['password']);
        }

        if (isset($user['email']) && $exists = $this->getUserByEmail($user['email'])) {
            throw new Exception("An user with this email addresss is already registered.");
        }

        if ($exists = $this->getUser($user['username'])) {
            throw new Exception("User with this username already exists.");
        }

        $users = $this->getUsers();
        $users[$this->getSite()->getUniqId()] = $user;

        $this->writeUsers($users);
    }

    public function updateUser($username, $infos = array(), $validate = true)
    {
        if (!$user = $this->getUser($username)) {
            return false;
            // throw new Exception("User with this username doesn't exists.");
        }

        $id = $user['id'];

        foreach ($infos as $key => $value) {
            if ($key == 'password') {
                $hasher = new Ld_Auth_Hasher(8, TRUE);
                $user['hash'] = $hasher->HashPassword($value);
            } else {
                $user[$key] = $value;
            }
        }

        if ($validate) {
            $this->validateUser($user);
        }

        $users = $this->getUsers();
        $users[$id] = $user;

        $this->writeUsers($users);
    }

    public function deleteUser($username)
    {
        if (!$user = $this->getUser($username)) {
            throw new Exception("User with this username doesn't exists.");
        }

        $id = $user['id'];

        $users = $this->getUsers();
        unset($users[$id]);

        $this->writeUsers($users);
    }

    public function writeUsers($users)
    {
        $this->_users = $users;
        Ld_Files::putJson($this->getSite()->getDirectory('dist') . '/users.json', $users);
    }

    public function validateUser($user)
    {
        // Username
        $validateUsername = new Zend_Validate();
        $validateUsername->addValidator( new Zend_Validate_StringLength(array('min' => 1, 'max' => 64)) )
                         ->addValidator( new Zend_Validate_Alnum() );
        if (!$validateUsername->isValid($user['username'])) {
            $messages = array_values($validateUsername->getMessages());
            throw new Exception($messages[0]);
        }

        // Password
        if (empty($user['hash']) && isset($user['password'])) {
            $validatePassword = new Zend_Validate();
            $validatePassword->addValidator( new Zend_Validate_StringLength(array('min' => 6, 'max' => 64)) );
            if (!$validatePassword->isValid($user['password'])) {
                throw new Exception('Password should be betwen 6 and 64 characters.');
            }
        }

        // Email
        if (isset($user['email'])) {
            $validateEmail = new Zend_Validate_EmailAddress();
            if (!$validateEmail->isValid($user['email'])) {
                throw new Exception('Email is invalid.');
            }
        }
    }

}
