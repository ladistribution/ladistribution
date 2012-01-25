<?php

class Ld_Services_Contacts_Identica extends Ld_Services_Contacts_Twitter
{

    public function getContacts($type, $normalised = true)
    {
        $baseApiUrl = $this->getService()->getBaseApiUrl();

        foreach (array('followers', 'friends') as $relationType) {
            $$relationType = $result = $this->request($baseApiUrl . '/statuses/' . $relationType . '.json');
        }

        // Merge
        $allContacts = array();
        foreach (array('followers', 'friends') as $relationType) {
            foreach ($$relationType as $contact) {
                $id = $contact['id'];
                if (empty($allContacts[$id])) {
                    if ($relationType == 'followers') {
                        $contact['follower'] = true;
                    }
                    // if ($relationType == 'friends') {
                    //     $contact['following'] = true;
                    // }
                    $allContacts[$id] = $contact;
                }
            }
        }

        $return = array();
        foreach ($allContacts as $contact) {
            if ($normalised) {
                $nContact = $this->getService()->_normaliseUser($contact);
                $nContact['follower'] = isset($contact['follower']) ? $contact['follower'] : false;
                $nContact['following'] = isset($contact['following']) ? $contact['following'] : false;
                $nContact['mutual'] = $nContact['following'] && $nContact['follower'];
                $return[] = $nContact;
            } else {
                $return[] = $contact;
            }
        }

        return $return;
    }

    public function follow($id, $username)
    {
        parent::follow($id, $username);
        // clean cache
        $this->getService()->cleanCache($baseApiUrl . '/statuses/friends.json');
    }

}
