<?php

class Ld_Auth
{

    protected static $_realm = 'ld';

    public static function getUsers()
    {
        $users = array();
        $filename = LD_DIST_DIR . '/users.json';
        if (file_exists($filename)) {
            $users = Zend_Json::decode(file_get_contents($filename));
            foreach ($users as $key => $user) {
                $users[$key]['id'] = $key;
                $users[$key]['identities'] = array(LD_BASE_URL . 'identity/' . $user['username']);
            }
        }
        return $users;
    }

    public static function getUser($username)
    {
        $users = self::getUsers();
        foreach ($users as $user) {
            if ($user['username'] == $username) {
                return $user;
            }
        }
        return null;
    }

    public static function addUser($user)
    {
        $user['password'] = sha1($user['password']);
        $username = $user['username'];

        if ($exists = self::getUser($username)) {
            throw new Exception("User with this username already exists.");
        }

        $users = $this->getUsers();
        $users[uniqid()] = $user;

        self::_writeUsers($users);
    }

    public static function deleteUser($username)
    {
        if (!$user = self::getUser($username)) {
            throw new Exception("User with this username doesn't exists.");
        }

        $id = $user['id'];

        $users = $this->getUsers();
        unset($users[$id]);

        self::_writeUsers($users);
    }

    protected static function _writeUsers($users)
    {
        file_put_contents(LD_DIST_DIR . '/users.json', Zend_Json::encode($users));
    }

    public static function authenticate()
    {
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $users = self::getUsers();
            if (empty($users)) {
                throw new Exception("No user defined."); 
            }
            foreach ($users as $user) {
                if ($_SERVER['PHP_AUTH_USER'] == $user['username'] && sha1($_SERVER['PHP_AUTH_PW']) == $user['hash']) {
                    return $user['username'];
                }
            }
        }
        self::unauthorized();
    }

    public static function unauthorized($message = 'Unauthorized')
    {
        $realm = self::$_realm;
        header("HTTP/1.0 401 Unauthorized");
        header("WWW-Authenticate: Basic realm=\"$realm\"");
        die($message);
    }

}