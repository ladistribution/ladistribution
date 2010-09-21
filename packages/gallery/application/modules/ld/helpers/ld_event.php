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
                $user = ORM::factory("user");
                $user->name = $ld_user['username'];
                $user->full_name = $ld_user['fullname'];
                $user->password = 'XXXXX';
                $user->hashed_password = $ld_user['hash'];
                $user->email = $ld_user['email'];
                $user->save();
            }

            $instance = Zend_Registry::get('application');

            $role = $instance->getUserRole();
            if ($role == 'administrator' && $user->admin != 1) {
                $user->admin = 1;
                $user->save();
            }

            identity::set_active_user($user);
        }

    }

    static function user_logout()
    {
        Ld_Auth::logout();
    }

    static function admin_menu($menu, $theme)
    {
        // $menu->remove("settings_menu");
        // $menu->remove("modules");
    }

}
