<?php

class Ld_Services_Contacts_Twitter extends Ld_Services_Contacts_Abstract
{

    function getScreenName()
    {
        $tUser = $this->getService()->_getUser();
        return $tUser['screen_name'];
    }

    function getIds($relationType = 'followers')
    {
        $user = $this->getRawUser();
        $userId = $user['id'];
        $screenName = $user['screen_name'];
        // From Cache
        $cacheKey = "twitter-$userId-$relationType";
        if ($value = $this->getValue($cacheKey)) {
            return $value;
        }
        // Fallback
        $params = array('cursor' => '-1', 'screen_name' => $screenName);
        $result = $this->request($this->getService()->getBaseApiUrl() . '/' . $relationType . '/ids.json', 'GET', $params);
        $ids = isset($result['ids']) ? $result['ids'] : $result;
        $this->setValue($cacheKey, $ids);
        return $ids;
    }

    function getContacts($type = 'all', $normalised = true)
    {
        $users = array();
        $lookup = array();

        $screenName = $this->getScreenName();

        $baseApiUrl = $this->getService()->getBaseApiUrl();

        foreach (array('followers', 'friends') as $relationType) {
          $ids = $$relationType = $this->getIds($relationType);
          foreach ($ids as $id) {
            if ($value = $this->getValue("twitter-$id")) {
              $users[$id] = $value;
            } else {
              $lookup[] = $id;
            }
          }
        }

        if (!empty($lookup)) {
          foreach (array_chunk($lookup, 99) as $chunk) {
              $params = array('user_id' => implode(',', $chunk));
              $lookup_users = $this->request($baseApiUrl . '/users/lookup.json', 'GET', $params);
              foreach ($lookup_users as $user) {
                if (empty($user['id'])) {
                    continue;
                }
                $id = $user['id'];
                $this->setValue("twitter-$id", $user);
                $users[$id] = $user;
              }
          }
        }

        switch ($type) {
            case 'friends':
                $ids = $friends; break;
            case 'followers':
                $ids = $followers; break;
            case 'followers_not_friends':
                $ids = array_diff($followers, $friends); break;
            case 'friends_not_followers':
                $ids = array_diff($friends, $followers); break;
            case 'mutual_friends':
                $ids = array_intersect($friends, $followers); break;
            // case 'all':
            default:
                $ids = array_merge($friends, $followers); break;
        }

        $ids = array_unique($ids);

        $return = array();
        foreach ($ids as $id) {
            if (empty($users[$id])) {
                continue;
            }
            if ($normalised) {
                $nUser = $this->getService()->_normaliseUser($users[$id]);
                $nUser['following'] = in_array($id, $friends);
                $nUser['follower'] = in_array($id, $followers);
                $nUser['mutual'] = $nUser['following'] && $nUser['follower'];
                $return[] = $nUser;
            } else {
                $return[] = $users[$id];
            }
        }
        return $return;
    }

    function follow($id)
    {
        $baseApiUrl = $this->getService()->getBaseApiUrl();
        $params = array('user_id' => $id);
        $result = $this->request($baseApiUrl . '/friendships/create.json', 'POST', $params);
        // update cache ...
    }

}
