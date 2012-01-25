<?php

class Ld_Services_Contacts_Github extends Ld_Services_Contacts_Abstract
{

    public function getLogins($relationType = 'followers')
    {
        $user = $this->getRawUser();
        $userId = $user['id'];
        // From Cache
        $cacheKey = "github-$userId-$relationType";
        if ($value = $this->getValue($cacheKey)) {
            return $value;
        }
        // Fallback
        $result = $this->getService()->request("https://api.github.com/user/$relationType");
        $logins = array();
        foreach ($result as $user) {
            $logins[] = $user['login'];
        }
        $this->setValue($cacheKey, $logins, 300); /* should be less than feed cache */
        return $logins;
    }

    public function getContacts($type = 'all', $normalised = true)
    {
        $users = array();
        $lookup = array();

        foreach (array('followers', 'following') as $relationType) {
           $logins = $$relationType = $this->getLogins($relationType);
           foreach ($logins as $login) {
             if ($value = $this->getValue("github-$login")) {
               $users[$login] = $value;
             } else {
               $lookup[] = $login;
             }
           }
        }

        $lookup = array_unique($lookup);

        if (!empty($lookup)) {
            foreach ($lookup as $login) {
                $user = $this->getService()->request("https://api.github.com/users/$login");
                $this->setValue("github-$login", $user);
                $users[$login] = $user;
            }
        }

        switch ($type) {
            case 'friends':
                $ids = $following; break;
            case 'followers':
                $ids = $followers; break;
            case 'followers_not_friends':
                $ids = array_diff($followers, $following); break;
            case 'friends_not_followers':
                $ids = array_diff($following, $followers); break;
            case 'mutual_friends':
                $ids = array_intersect($following, $followers); break;
            default:
                $ids = array_merge($following, $followers); break;
        }

        $ids = array_unique($ids);

        $return = array();
        foreach ($ids as $id) {
            if ($normalised) {
                $nUser = $this->getService()->_normaliseUser($users[$id]);
                $nUser['following'] = in_array($id, $following);
                $nUser['follower'] = in_array($id, $followers);
                $nUser['mutual'] = $nUser['following'] && $nUser['follower'];
                $return[] = $nUser;
            } else {
                $return[] = $users[$id];
            }
        }
        return $return;
    }

    public function follow($id, $username)
    {
        $result = $this->request('https://api.github.com/user/following/' . $username, 'PUT');
        // update cache ...
        $user = $this->getRawUser();
        $userId = $user['id'];
        $cacheKey = "github-$userId-following";
        $logins = $this->getLogins('following');
        $logins[] = $username;
        $this->setValue($cacheKey, $logins);
    }

}
