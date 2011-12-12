<?php

class Ld_Services_Contacts_Linkedin extends Ld_Services_Contacts_Abstract
{

    public function getContacts($list = null, $normalised = true)
    {
        $fields = implode(',' , $this->getService()->getFields());

        $result = $this->request('http://api.linkedin.com/v1/people/~/connections:(' . $fields .')');

        $connections = $result['values'];

        // Filter/Normalise contacts
        $contacts = array();
        foreach ($connections as $index => $connection) {
            if ($normalised) {
                $contact = $this->getService()->_normaliseUser($connection);
                $contact['mutual'] = true;
                $contacts[] = $contact;
            } else {
                $contacts[] = $connection;
            }
        }
        return $contacts;
    }

}
