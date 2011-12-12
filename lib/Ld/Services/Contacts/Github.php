<?php

class Ld_Services_Contacts_Github extends Ld_Services_Contacts_Abstract
{

    public function getContacts($type = 'all', $normalised = true)
    {
        $users = array();
        $lookup = array();

        foreach (array('followers', 'following') as $relationType) {
           $$relationType = $this->getService()->request("https://api.github.com/user/$relationType");
           foreach ($$relationType as $user) {
             $id = $user['login'];
             if ($value = $this->getValue("github-user-$id")) {
               $users[$id] = $value;
             } else {
               $lookup[] = $id;
             }
           }
        }

        $lookup = array_unique($lookup);

        if (!empty($lookup)) {
            foreach ($lookup as $id) {
                $user = $this->getService()->request("https://api.github.com/users/$id");
                $this->setValue("github-user-$id", $user);
                $users[$id] = $user;
            }
        }

        $followers_ids = array();
        foreach ($followers as $user) {
            $followers_ids[] = $user['login'];
        }
        $following_ids = array();
        foreach ($following as $user) {
            $following_ids[] = $user['login'];
        }

        switch ($type) {
            case 'friends':
                $ids = $following_ids; break;
            case 'followers':
                $ids = $followers_ids; break;
            case 'followers_not_friends':
                $ids = array_diff($followers_ids, $following_ids); break;
            case 'friends_not_followers':
                $ids = array_diff($following_ids, $followers_ids); break;
            case 'mutual_friends':
                $ids = array_intersect($following_ids, $followers_ids); break;
            default:
                $ids = array_merge($following_ids, $followers_ids); break;
        }

        $ids = array_unique($ids);

        $return = array();
        foreach ($ids as $id) {
            if ($normalised) {
                $nUser = $this->getService()->_normaliseUser($users[$id]);
                $nUser['following'] = in_array($id, $following_ids);
                $nUser['follower'] = in_array($id, $followers_ids);
                $nUser['mutual'] = $nUser['following'] && $nUser['follower'];
                $return[] = $nUser;
            } else {
                $return[] = $users[$id];
            }
        }
        return $return;
    }

}
