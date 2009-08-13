<?php defined("SYSPATH") or die("No direct script access.");

class ld_event_Core
{

    static function gallery_ready()
    {
        if (Ld_Auth::isAuthenticated()) {

            $ld_user = Ld_Auth::getUser();

            try {
                $user = user::lookup_by_name($ld_user['username']);
            } catch (Exception $e) {
                $user = null;
            }

            if (empty($user)) {
                $user = user::create($ld_user['username'], $ld_user['fullname']);
            }

            $instance = Zend_Registry::get('application');

            $role = $instance->getUserRole();
            if ($role == 'administrator' && $user->admin != 1) {
                $user->admin = 1;
                $user->save();
            }

            user::login($user);
        }

    }

    static function user_logout()
    {
        Ld_Auth::logout();
    }

}
