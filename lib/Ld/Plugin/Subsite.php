<?php

class Ld_Plugin_Subsite
{

    public function load()
    {
        Ld_Plugin::addAction('Slotter:preferences', array($this, 'restricted_globalSettings'), 10, 2);
        Ld_Plugin::addAction('Slotter:acl', array($this, 'restricted_initAcl'), 10);
    }

    public function restricted_globalSettings($preferences)
    {
        $disabledPreferences = array('host', 'path', 'root_admin', 'open_registration');
        foreach ($preferences as $index => $preference) {
            if (in_array($preference['name'], $disabledPreferences)) {
                unset($preferences[$index]);
            }
        }
        return $preferences;
    }

    public function restricted_initAcl($acl)
    {
        $acl->deny('admin', 'repositories', 'manage');
        $acl->deny('admin', 'databases', 'manage');
        $acl->deny('admin', 'domains', 'manage');
        $acl->deny('admin', 'users', 'manage');
        $acl->deny('admin', 'plugins', 'manage');
    }

}
