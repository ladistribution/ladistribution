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
            'version' => '0.5.82',
            'description' => Ld_Translate::translate('Allow email delivery configuration.'),
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

        Ld_Plugin::addAction('Wordpress:plugin', array($this, 'wordpress_init'));
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

    public function wordpress_init()
    {
        add_action('phpmailer_init', array($this, 'wordpress_phpmailer_init'));
    }

    /* Inspired from http://wordpress.org/extend/plugins/configure-smtp/ */

    public function wordpress_phpmailer_init($phpmailer)
    {
        $server = $site->getConfig('mail_server', 'localhost');
        $username = $site->getConfig('mail_username', '');
        $password = $site->getConfig('mail_password', '');
        if (strpos($server, ':')) {
            list($server, $port) = explode(':', $server);
        }
        $phpmailer->IsSMTP();
        $phpmailer->Host = $server;
        $phpmailer->Port = isset($port) ? $port : 25;
        $phpmailer->SMTPAuth = (!empty($username) && !empty($password)) ? true : false;
        if ($phpmailer->SMTPAuth) {
            $phpmailer->Username = $username;
            $phpmailer->Password = $password;
        }
    }

}
