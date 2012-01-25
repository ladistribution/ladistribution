<?php

class Ld_Services_Contacts_Foursquare extends Ld_Services_Contacts_Abstract
{

    public function getContacts($list = null, $normalised = true)
    {
        $friends = $this->request('https://api.foursquare.com/v2/users/self/friends?limit=500&v=20120107');

        // Error
        if (empty($friends['response']['friends'])) {
            return array();
        }

        $requests = $this->request('https://api.foursquare.com/v2/users/requests?limit=500&v=20120107');

        $allContacts = array_merge($friends['response']['friends']['items'], $requests['response']['requests']);

        // Filter/Normalise contacts
        $contacts = array();
        foreach ($allContacts as $fContact) {
            if ($normalised) {
                $nContact = $this->getService()->normaliseUser($fContact);
                if ($fContact['relationship'] == 'friend') {
                    $nContact['mutual'] = $nContact['following'] = $nContact['follower'] = true;
                    $nContact['label'] = 'Friend';
                } elseif ($fContact['relationship'] == 'pendingMe') {
                    $nContact['mutual'] = $nContact['following'] = false;
                    $nContact['follower'] = true;
                }
                $contacts[] = $nContact;
            } else {
                $contacts[] = $fContact;
            }
        }

        return $contacts;
    }

    public function follow($id, $username)
    {
        // if pendingMe -> accept & clean cache
        $requests = $this->request('https://api.foursquare.com/v2/users/requests?limit=500&v=20120107');
        if (!empty($requests['response']['requests'])) {
            foreach ($requests['response']['requests'] as $fContact) {
                if ($fContact['id'] == $id && $fContact['relationship'] == 'pendingMe') {
                    // accept
                    $result = $this->request("https://api.foursquare.com/v2/users/$id/approve", 'POST', array(), false);
                    // clean cache
                    $this->getService()->cleanCache('https://api.foursquare.com/v2/users/self/friends?limit=500&v=20120107');
                    $this->getService()->cleanCache('https://api.foursquare.com/v2/users/requests?limit=500&v=20120107');
                    return true;
                }
            }
        }
        // else friend request
        $result = $this->request("https://api.foursquare.com/v2/users/$id/request", 'POST', array(), false);
        // clean cache, which cache ?
        // $this->getService()->cleanCache('https://api.foursquare.com/v2/users/requests?limit=500&v=20120107');
    }

}
