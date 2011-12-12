<?php

class Ld_Services_Contacts_Twitter extends Ld_Services_Contacts_Abstract
{

    function getScreenName()
    {
        $tUser = $this->getService()->_getUser();
        return $tUser['screen_name'];
    }

    function getContacts($type = 'all', $normalised = true)
    {
        $users = array();
        $lookup = array();

        $screenName = $this->getScreenName();

        $baseApiUrl = $this->getService()->getBaseApiUrl();

        foreach (array('followers', 'friends') as $relationType) {
          // $$relationType = $result = $this->request($baseApiUrl . '/' . $relationType . '/ids.json?cursor=-1&screen_name=' . $screenName);
          $params = array('cursor' => '-1', 'screen_name' => $screenName);
          $$relationType = $result = $this->request($baseApiUrl . '/' . $relationType . '/ids.json', 'GET', $params);
          // var_dump($result);
          $ids = isset($result['ids']) ? $result['ids'] : $result;
          foreach ($ids as $id) {
            if ($value = $this->getValue("twitter-user-$id")) {
              $users[$id] = $value;
            } else {
              $lookup[] = $id;
            }
          }
        }

        if (!empty($lookup)) {
          foreach (array_chunk($lookup, 100) as $chunk) {
              $params = array('user_id' => implode(',', $chunk));
              $lookup_users = $this->request($baseApiUrl . '/users/lookup.json', 'GET', $params);
              foreach ($lookup_users as $user) {
                $id = $user['id'];
                $this->setValue("twitter-user-$id", $user);
                $users[$id] = $user;
              }
          }
        }

        switch ($type) {
            case 'friends':
                $ids = $friends['ids']; break;
            case 'followers':
                $ids = $followers['ids']; break;
            case 'followers_not_friends':
                $ids = array_diff($followers['ids'], $friends['ids']); break;
            case 'friends_not_followers':
                $ids = array_diff($friends['ids'], $followers['ids']); break;
            case 'mutual_friends':
                $ids = array_intersect($friends['ids'], $followers['ids']); break;
            // case 'all':
            default:
                $ids = array_merge($friends['ids'], $followers['ids']); break;
        }

        $ids = array_unique($ids);

        $return = array();
        foreach ($ids as $id) {
            if ($normalised) {
                $nUser = $this->getService()->_normaliseUser($users[$id]);
                $nUser['following'] = in_array($id, $friends['ids']);
                $nUser['follower'] = in_array($id, $followers['ids']);
                $nUser['mutual'] = $nUser['following'] && $nUser['follower'];
                $return[] = $nUser;
            } else {
                $return[] = $users[$id];
            }
        }
        return $return;
    }

    function follow($screen_name)
    {
        $baseApiUrl = $this->getService()->getBaseApiUrl();
        $params = array('screen_name' => $screen_name);
        $result = $this->request($baseApiUrl . '/friendships/create.json', 'POST', $params);
        // update cache ...
    }

}
