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
        Ld_Plugin::addFilter('Bbpress:getAvatarUrl', array($this, 'bbpress_get_avatar_url'), 10, 4);
    }

    public function getAvatarUrl($url, $user, $size)
    {
        $default = $url;
        $email = isset($user) && isset($user['email']) ? $user['email'] : '';
        if (!empty($email)) {
            return $this->getGravatarUrl($email, $size, $default);
        }
        return $url;
    }

    public function getGravatarUrl($email, $size, $default)
    {
        $hash = md5( strtolower( $email ) );
        $host = sprintf( "%d.gravatar.com", ( hexdec( $hash{0} ) % 2 ) );
        $url = "http://$host/avatar/{$hash}?s={$size}&d=" . urlencode( $default );
        return $url;
    }

    public function bbpress_get_avatar_url($url, $id_or_email, $size, $default)
    {
        if (!$email = bb_get_user_email($id_or_email)) {
            $email = $id_or_email;
        }
        return $this->getGravatarUrl($email, $size, $url);
    }

}
