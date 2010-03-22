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

    public function getUsers()
    {
        $users = $this->_users;

        if (empty($users)) {
            $users = Ld_Files::getJson($this->getSite()->getDirectory('dist') . '/users.json');
        }

        // uasort($users, array($this, "_sortByOrder"));

        foreach ((array)$users as $key => $user) {
            $users[$key]['id'] = $key;
            // if (empty($users[$key]['fullname'])) {
            //     $users[$key]['fullname'] = $users[$key]['username'];
            // }
        }

        return $this->_users = $users;
    }

    public function getUser($username)
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['username'] == $username) {
                return $user;
            }
        }
        return null;
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

    public function addUser($user)
    {
        $hasher = new Ld_Auth_Hasher(8, TRUE);

        if (isset($user['password'])) {
            $user['hash'] = $hasher->HashPassword($user['password']);
            unset($user['password']);
        }

        if ($exists = $this->getUser($user['username'])) {
            throw new Exception("User with this username already exists.");
        }

        $users = $this->getUsers();
        $users[$this->getSite()->getUniqId()] = $user;

        $this->writeUsers($users);
    }

    public function updateUser($username, $infos = array())
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

}
