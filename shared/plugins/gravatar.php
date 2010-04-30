<?php

class Ld_Plugin_Gravatar
{

    public function infos()
    {
        return array(
            'name' => 'Gravatar',
            'url' => 'http://ladistribution.net/wiki/plugins/#gravatar',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.3',
            'description' => Ld_Translate::translate('Display avatars using the Gravatar service.'),
            'license' => 'MIT / GPL'
        );
    }

    public function status()
    {
        return array(1, sprintf(Ld_Translate::translate('%s is running.'), 'Gravatar'));
    }

    public function load()
    {
        Ld_Plugin::addFilter('Ui:getAvatarUrl', array($this, 'getAvatarUrl'), 10, 3);
    }

    public function getAvatarUrl($url, $user, $size)
    {
        $default = $url;

        $email = isset($user) && isset($user['email']) ? $user['email'] : '';

        if ( !empty($email) ) {
            $hash = md5( strtolower( $email ) );
            $host = sprintf( "http://%d.gravatar.com", ( hexdec( $hash{0} ) % 2 ) );
            $url = "$host/avatar/{$hash}?s={$size}";
            $url .= '&amp;d=' . urlencode( $default );
            return $url;
        }

        return $url;
    }

}
