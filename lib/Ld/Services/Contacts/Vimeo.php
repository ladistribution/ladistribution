<?php

class Ld_Services_Contacts_Vimeo extends Ld_Services_Contacts_Abstract
{

    public function getContacts($list = null, $normalised = true)
    {
        $result = $this->request('http://vimeo.com/api/rest/v2', 'GET', array('method' => 'vimeo.contacts.getAll', 'per_page' => 50));
        $friends =  isset($result['contacts']['contact']) ? $result['contacts']['contact'] : array();

        $result = $this->request('http://vimeo.com/api/rest/v2', 'GET', array('method' => 'vimeo.contacts.getWhoAdded', 'per_page' => 50));
        $followers = isset($result['contacts']['contact']) ? $result['contacts']['contact'] : array();

        // Merge
        $allContacts = array();
        foreach (array('followers', 'friends') as $relationType) {
            foreach ($$relationType as $contact) {
                $id = $contact['id'];
                if (empty($allContacts[$id])) {
                    $allContacts[$id] = $contact;
                }
                if ($relationType == 'followers') {
                    $allContacts[$id]['follower'] = true;
                } elseif ($relationType == 'friends') {
                    $allContacts[$id]['following'] = true;
                }
            }
        }

        // Filter/Normalise contacts
        $contacts = array();
        foreach ($allContacts as $vContact) {
            if ($normalised) {
                $nContact = $this->getService()->normaliseUser($vContact);
                $nContact['follower'] = isset($vContact['follower']) ? $vContact['follower'] : false;
                $nContact['following'] = isset($vContact['following']) ? $vContact['following'] : false;
                $nContact['mutual'] = $nContact['following'] && $nContact['follower'];
                $contacts[] = $nContact;
            } else {
                $contacts[] = $vContact;
            }
        }

        return $contacts;
    }

    public function follow($id, $username)
    {
        // follow
        $result = $this->request('http://vimeo.com/api/rest/v2', 'GET', array('method' => 'vimeo.people.addContact', 'user_id' => $id), false);
        // clean cache
        $this->getService()->cleanCache('http://vimeo.com/api/rest/v2', 'GET', array('method' => 'vimeo.contacts.getAll', 'per_page' => 50));
    }

}
