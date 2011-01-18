<?php

class Ld_Plugin_Mail
{

    public function infos()
    {
        return array(
            'name' => 'Mail',
            'url' => 'http://ladistribution.net/wiki/plugins/#mail',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0',
            'description' => Ld_Translate::translate('Make Mail backend configurable.'),
            'license' => 'MIT / GPL'
        );
    }

    public function load()
    {
        $site = Zend_Registry::get('site');
        $server = $site->getConfig('mail_server', 'localhost');
        $username = $site->getConfig('mail_username', '');
        $password = $site->getConfig('mail_password', '');
        $config = array();
        $config['port'] = '25';
        if (strpos($server, ':')) {
            list($server, $config['port']) = explode(':', $server);
        }
        if (!empty($username) && !empty($password)) {
            $config['auth'] = 'login';
            $config['username'] = $username;
            $config['password'] = $password;
        }
        $transport = new Zend_Mail_Transport_Smtp($server, $config);
        Zend_Mail::setDefaultTransport($transport);
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'mail_backend', 'label' => Ld_Translate::translate('Backend'),
            'type' => 'list', 'defaultValue' => 'smtp', 'options' => array(
                array('value' => 'smtp', 'label' => 'SMTP')
            )
        );
        $preferences[] = array(
            'name' => 'mail_server', 'label' => Ld_Translate::translate('Server'),
            'type' => 'text', 'defaultValue' => ''
        );
        $preferences[] = array(
            'name' => 'mail_username', 'label' => Ld_Translate::translate('Username'),
            'type' => 'text', 'defaultValue' => ''
        );
        $preferences[] = array(
            'name' => 'mail_password', 'label' => Ld_Translate::translate('Password'),
            'type' => 'text', 'defaultValue' => ''
        );
        return $preferences;
    }

}
