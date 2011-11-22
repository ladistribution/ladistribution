<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Model
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Model_Users extends Ld_Model_Collection
{

    protected $_collectionId = 'users';

    protected $_users = array();

    public function getUsers($params = array())
    {
        $users = $this->_users;

        if (empty($users)) {
            $users = $this->getAll();
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

    public function getUserBy($key, $value)
    {
        foreach ($this->getUsers() as $id => $user) {
            if (!empty($user[$key]) && $user[$key] == $value) {
                return $user;
            }
        }
    }

    public function getUser($username)
    {
        // by username
        $user = $this->getUserBy('username', $username);
        // by url
        if (empty($user) && Zend_Uri_Http::check($username)) {
            $user = $this->getUserByUrl($username);
        }
        // by email
        $validator = new Zend_Validate_EmailAddress();
        if (empty($user) && $validator->isValid($username)) {
            $user = $this->getUserBy('email', $username);
        }
        return $user;
    }

    public function getUserByUrl($url)
    {
        foreach ($this->getUsers() as $id => $user) {
            if (!empty($user['identities'])) {
                foreach ($user['identities'] as $identity) {
                    if ($identity == $url) {
                        return $user;
                    }
                }
            }
        }
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

        if (isset($user['email']) && $exists = $this->getUserBy('email', $user['email'])) {
            throw new Exception("An user with this email addresss is already registered.");
        }

        if ($exists = $this->getUserBy('username', $user['username'])) {
            throw new Exception("User with this username already exists.");
        }

        $this->getBackend()->create($user);
    }

    public function updateUser($username, $infos = array(), $validate = true)
    {
        if (!$user = $this->getUserBy('username', $username)) {
            throw new Exception("User with this username doesn't exists.");
        }

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

        $this->getBackend()->update($user['id'], $user);
    }

    public function deleteUser($username)
    {
        if (!$user = $this->getUserBy('username', $username)) {
            throw new Exception("User with this username doesn't exists.");
        }

        $this->getBackend()->delete($user['id']);
    }

    public function validateUser($user)
    {
        // Username
        $validateUsername = new Zend_Validate();
        $validateUsername->addValidator( new Zend_Validate_StringLength(array('min' => 1, 'max' => 64, 'encoding' => 'utf-8')) );
        if (!$validateUsername->isValid($user['username'])) {
            $messages = array_values($validateUsername->getMessages());
            throw new Exception($messages[0]);
        }

        // Password
        if (empty($user['hash']) && isset($user['password'])) {
            $validatePassword = new Zend_Validate();
            $validatePassword->addValidator( new Zend_Validate_StringLength(array('min' => 4, 'max' => 64, 'encoding' => 'utf-8')) );
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
