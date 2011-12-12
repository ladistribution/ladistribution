<?php

class Ld_Services_Contacts_Soundcloud extends Ld_Services_Contacts_Twitter
{

    public function getContacts($type, $normalised = true)
    {
        foreach (array('followings', 'followers') as $relationType) {
          $$relationType = $result = $this->request("https://api.soundcloud.com/me/$relationType.json");
        }

        // Merge
        $allContacts = array();
        foreach (array('followings', 'followers') as $relationType) {
            foreach ($$relationType as $contact) {
                $id = $contact['id'];
                if (empty($allContacts[$id])) {
                    $allContacts[$id] = $contact;
                }
                if ($relationType == 'followers') {
                    $allContacts[$id]['follower'] = true;
                } elseif ($relationType == 'followings') {
                    $allContacts[$id]['following'] = true;
                }
            }
        }

        // Normalise
        $return = array();
        foreach ($allContacts as $contact) {
            if ($normalised) {
                $nContact = $this->getService()->normaliseUser($contact);
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

}
