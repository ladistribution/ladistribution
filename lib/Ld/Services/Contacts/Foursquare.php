<?php

class Ld_Services_Contacts_Foursquare extends Ld_Services_Contacts_Abstract
{

    public function getContacts($list = null, $normalised = true)
    {
        $result = $this->request('https://api.foursquare.com/v2/users/self/friends?limit=500&v=20120107');

        $allContacts = $result['response']['friends']['items'];

        // Filter/Normalise contacts
        $contacts = array();
        foreach ($allContacts as $fContact) {
            if ($normalised) {
                $nContact = $this->getService()->normaliseUser($fContact);
                $nContact['mutual'] = $nContact['following'] = $nContact['follower'] = true;
                $contacts[] = $nContact;
            } else {
                $contacts[] = $fContact;
            }
        }

        return $contacts;
    }

}
