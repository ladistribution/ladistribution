<?php

class Ld_Plugin_Subsite
{

    public function load()
    {
        Ld_Plugin::addAction('Slotter:preferences', array($this, 'slotterPreferences'));
        Ld_Plugin::addAction('Slotter:acl', array($this, 'slotterAcl'));
    }

    public function slotterPreferences($preferences)
    {
        $disabledPreferences = array('host', 'path', 'root_admin');
        foreach ($preferences as $index => $preference) {
            if (in_array($preference['name'], $disabledPreferences)) {
                unset($preferences[$index]);
            }
            if ($preference['name'] == 'open_registration' && $this->isParentRegistrationClosed()) {
                unset($preferences[$index]);
            }
        }
        return $preferences;
    }

    public function slotterAcl($acl)
    {
        $acl->deny('admin', 'repositories', 'manage');
        $acl->deny('admin', 'databases', 'manage');
        $acl->deny('admin', 'domains', 'manage');
        $acl->deny('admin', 'users', 'manage');
        $acl->deny('admin', 'plugins', 'manage');
    }

    public function isParentRegistrationClosed()
    {
        $site = Zend_Registry::get('site');
        $parent = $site->getParentSite();
        return $parent->getConfig('open_registration') != 1;
    }

}
