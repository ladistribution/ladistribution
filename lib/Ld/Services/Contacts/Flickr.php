<?php

class Ld_Services_Contacts_Flickr extends Ld_Services_Contacts_Abstract
{

    public function getContacts($list = null, $normalised = true)
    {
        $result = $this->request('http://api.flickr.com/services/rest', 'GET', array('method' => 'flickr.contacts.getList'));

        // Filter/Normalise contacts
        $contacts = array();
        foreach ($result['contacts']['contact'] as $fContact) {
            if ($normalised) {
                $contacts[] = $this->normaliseUser($fContact);
            } else {
                $contact['following'] = true;
                $contacts[] = $fContact;
            }
        }

        return $contacts;
    }

    public function normaliseUser($fContact)
    {
        $user = array();
        $user['id'] = $id = $fContact['nsid'];
        $user['guid'] = 'flickr:' . $id;
        $user['username'] = $fContact['username'];
        $user['fullname'] = $fContact['realname'];
        $path = isset($fContact['path_alias']) ? $fContact['path_alias'] : $id;
        $user['url'] = "http://www.flickr.com/people/" . $path . "/";
        if (isset($fContact['iconfarm']) && isset($fContact['iconserver'])) {
            $user['avatar_url'] = 'http://farm' . $fContact['iconfarm'] . '.static.flickr.com/' .
                $fContact['iconserver'] . '/buddyicons/' . $fContact['nsid'].'.jpg';
        } else {
            $user['avatar_url'] = "http://www.flickr.com/buddyicons/$id.jpg";
        }
        if (isset($fContact['location'])) {
            $user['location'] = $fContact['location'];
        }

        if ($fContact['family']) {
            $user['label'] = 'Family';
        } else if ($fContact['friend']) {
            $user['label'] = 'Friend';
        } else {
            $user['label'] = 'Contact';
        }

        $user['_friend'] = $fContact['friend'];
        $user['_family'] = $fContact['family'];

        $user['following'] = true;
        return $user;
    }

}
