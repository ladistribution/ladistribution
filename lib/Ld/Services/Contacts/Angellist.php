<?php

class Ld_Services_Contacts_Angellist extends Ld_Services_Contacts_Abstract
{

    public function getContacts($type, $normalised = true)
    {
        $raw_user = $this->getService()->getRawUser();
        $user_id = $raw_user['id'];

        foreach (array('following', 'followers') as $relationType) {
          $result = $this->request("https://api.angel.co/1/users/$user_id/$relationType");
          $$relationType = $result['users'];
        }

        // Merge
        $allContacts = array();
        foreach (array('following', 'followers') as $relationType) {
            // var_dump($$relationType);
            foreach ($$relationType as $contact) {
                $id = $contact['id'];
                if (empty($allContacts[$id])) {
                    $allContacts[$id] = $contact;
                }
                if ($relationType == 'followers') {
                    $allContacts[$id]['follower'] = true;
                } elseif ($relationType == 'following') {
                    $allContacts[$id]['following'] = true;
                }
            }
        }

        // Me
        // $allContacts[$user_id] = $raw_user;

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

    public function follow($id)
    {
        $params = array('type' => 'user', 'id' => $id);
        $result = $this->request("https://api.angel.co/1/follows", 'POST', $params);
        // update cache ...
    }

}
