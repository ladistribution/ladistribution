<?php

class Ld_Services_Contacts_Facebook extends Ld_Services_Contacts_Abstract
{

    public function getLists()
    {
        $lists = $this->request("/me/friendlists");
        return $lists;
    }

    public function getTopFriends()
    {
        $lists = $this->request("/me/friendlists");
        foreach ($lists as $list) {
            if ($list['list_type'] == 'close_friends') {
                $id = $list['id'];
                return $this->request("/$id/members");
            }
        }
        return array();
    }

    public function getContacts($list = null, $normalised = true)
    {
        $fields = implode(',', array(
            'id',
            'username',
            'first_name', 'middle_name', 'last_name', 'name',
            'gender',
            'link', 'website',
            'locale', 'languages', 'timezone',
            'updated_time', 'verified',
            'hometown', 'location'
        ));

        // Get IDs from list
        if ($list !== null) {
            $members = $this->request("/$list/members");
            $ids = array();
            foreach ($members as $member) {
                $ids[] = $member['id'];
            }
        }

        // Get Top Friends IDs
        $topFriends = $this->getTopFriends();
        $topFriendsIds = array();
        foreach ($topFriends as $topFriend) {
            $topFriendsIds[] = $topFriend['id'];
        }

        // Get all friends
        $friends = $this->request("/me/friends?fields=$fields");

        // Filter/Normalise contacts
        $contacts = array();
        foreach ($friends as $index => $friend) {
            if (isset($ids) && !in_array($friend['id'], $ids)) {
                continue;
            }
            if ($normalised) {
                $contact = $this->getService()->_normaliseUser($friend);
                $contact['top'] = in_array($friend['id'], $topFriendsIds);
                $contact['mutual'] = $contact['following'] = $contact['follower'] = true;
                $contact['label'] = 'Friend';
                $contacts[] = $contact;
            } else {
                $contacts[] = $friend;
            }
        }
        return $contacts;
    }

}
