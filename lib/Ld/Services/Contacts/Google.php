<?php

require_once 'google-api-php-client/apiClient.php';
require_once 'google-api-php-client/contrib/apiPlusService.php';

class Ld_Services_Contacts_Google extends Ld_Services_Contacts_Abstract
{

    public function getContacts($type = 'all', $filter = 'id')
    {
        // Refresh the token "magically" if needed
        $this->getService()->refresh();

        $mixedContacts = $this->_getMixedContacts($type, 1000);

        $contacts = array();
        $plusRequests = 0;
        foreach ($mixedContacts as $contact) {
            if (!empty($contact['plus'])) {
                // we try to get user from cache, and if not in cache we fetch somes (5 maximum)
                $id = $contact['id'];
                $plusUser = $this->getValue("google-user-$id");
                if (empty($plusUser) && $plusRequests < 5) {
                    $plusUser = $this->getService()->_getPlusUser($contact['id']);
                    $this->setValue("google-user-$id", $plusUser);
                    $plusRequests ++;
                }
                // we complete the profile with the Google+ profile
                if (isset($plusUser)) {
                    if (isset($plusUser['displayName'])) {
                        $contact['fullname'] = $plusUser['displayName'];
                    }
                    if (isset($plusUser['gender'])) {
                        $contact['gender'] = $plusUser['gender'];
                    }
                    if (isset($plusUser['image'])) {
                        $contact['avatar_url'] = $plusUser['image']['url'];
                    }
                    if (isset($plusUser['urls'])) {
                        if (empty($contact['url_alias'])) {
                            $contact['url_alias'] = array();
                        }
                        foreach ($plusUser['urls'] as $url) {
                            if (empty($url['type'])) {
                                $contact['url_alias'][] = $url['value'];
                            }
                        }
                        array_unique($contact['url_alias']);
                    }
                    if (isset($plusUser['placesLived'])) {
                        foreach ($plusUser['placesLived'] as $placesLived) {
                            if (isset($placesLived['primary'])) {
                                $contact['location'] = $placesLived['value'];
                            }
                        }
                    }
                }
            }
            switch ($filter) {
                case 'id':
                case 'email':
                case 'url':
                case 'plus':
                    if (!empty($contact[$filter]))
                        $contacts[] = $this->getService()->_normaliseUser($contact);
                    break;
                case 'plus-email':
                    if (isset($contact['email']) && !empty($contact['plus']))
                         $contacts[] = $this->getService()->_normaliseUser($contact);
                    break;
                case 'phone':
                    if (!empty($contact['phone_numbers']))
                         $contacts[] = $this->getService()->_normaliseUser($contact);
                    break;
                default:
                     $contacts[] = $this->getService()->_normaliseUser($contact);
            }
        }

        return $contacts;
    }

    public function getKeyName($name)
    {
        $keyName = strtolower(trim($name));
        $keyName = str_replace(".", "", $keyName);
        $keyName = str_replace("-", "", $keyName);
        $keyName = str_replace("_", "", $keyName);
        $keyName = str_replace(" ", "", $keyName);
        // also remove accents ?
        return $name;
    }

    public function _getMixedContacts($type = 'all')
    {
        $contacts = array();

        $list = ($type == 'all' ? '@all' : 'mycontacts');

        $pocoContacts = $this->_getPocoContacts($list, 1000, 'topFriends');

        foreach ($this->_getPocoContacts($list, 1000, 'topFriends') as $contact) {
            $keyName = $this->getKeyName($contact['fullname']);
            if (empty($contacts[$keyName])) {
                $contacts[$keyName] = $contact;
            } else if (isset($contact['plus']) && empty($contacts[$keyName]['plus'])) {
                $contacts[$keyName] = $contact;
            } else if (isset($contact['url']) && empty($contacts[$keyName]['url'])) {
                $contacts[$keyName] = $contact;
            }
        }

        $gmailContacts = $this->_getGmailContacts();

        foreach ($this->_getGmailContacts() as $contact) {
            $keyName = $this->getKeyName($contact['fullname']);
            if (isset($contacts[$keyName])) {
                $contacts[$keyName]['email'] = $contact['email'];
                $contacts[$keyName]['email_alias'] = $contact['email_alias'];
                $contacts[$keyName]['phone_numbers'] = $contact['phone_numbers'];
                $contacts[$keyName]['updated_at'] = $contact['updated_at'];
            }
        }

        return $contacts;
    }

    public function _getGmailContacts($list = 'full', $count = 1000, $orderby = 'lastmodified', $sortorder = 'descending')
    {
        $contacts = array();

        $params = array(
            'alt' => 'json', 'max-results' => $count, 'orderby' => $orderby, 'sortorder' => $sortorder
        );
        $url  = 'https://www.google.com/m8/feeds/contacts/default/' . $list . '?' . http_build_query($params);
        $result = $this->request($url);

        foreach ($result['feed']['entry'] as $entry) {
            // No email alone
            if (empty($entry['title']['$t']) && empty($entry['gd$phoneNumber'])) {
                continue;
            }
            // No name alone
            if (empty($entry['gd$email']) && empty($entry['gd$phoneNumber'])) {
                continue;
            }
            $updated_at = $entry['updated']['$t'];
            $fullname = $entry['title']['$t'];
            $email_alias = array();
            if (!empty($entry['gd$email'])) {
                foreach ($entry['gd$email'] as $gdEmail) {
                    if (isset($gdEmail['primary'])) {
                        $email = $gdEmail['address'];
                    } else {
                        $email_alias[] = $gdEmail['address'];
                    }
                }
            }
            $phone_numbers = array();
            if (!empty($entry['gd$phoneNumber'])) {
                foreach ($entry['gd$phoneNumber'] as $gdPhoneNumber) {
                    $phone_numbers[] = $gdPhoneNumber['$t'];
                }
            }
            $contacts[] = compact('updated_at', 'fullname', 'email', 'email_alias', 'phone_numbers');
        }

        return $contacts;
    }

    public function _getPocoContacts($list = 'mycontacts', $count = 1000, $sortBy = null)
    {
        $contacts = array();

        $url = "https://www-opensocial.googleusercontent.com/api/people/@me/$list?count=$count";
        if ($sortBy == 'topFriends') {
            $url .= "&sortBy=topFriends";
        }
        $result = $this->request($url);

        foreach ($result['entry'] as $entry) {
            $contact = array();
            // Contact doesn't seems interesting whhen this info is missing
            if (empty($entry['name']['formatted']) || empty($entry['displayName'])) {
                continue;
            }
            // Contact doesn't seems interesting whhen this info is missing (only a name ?)
            if (empty($entry['id'])) {
                continue;
            } else {
                $contact['id'] = $entry['id'];
                $contact['guid'] = 'google:' . $entry['id'];
            }
            if ($entry['name']['formatted'] != $entry['displayName']) {
                // we might even skip these entries (low values)
                // continue;
                $contact['fullname'] = $entry['name']['formatted'];
                $contact['username'] = $entry['displayName'];
            } else {
                // continue;
                $contact['fullname'] = $entry['displayName'];
            }
            if (isset($entry['thumbnailUrl'])) {
                $contact['avatar_url'] = $entry['thumbnailUrl'];
            }
            if (isset($entry['profileUrl'])) {
                $contact['url'] = $entry['profileUrl'];
            }
            if (isset($contact['url']) && strpos($contact['url'], 'plus.google.com') !== false) {
                $contact['plus'] = true;
            }
            $contacts[] = $contact;
        }

        return $contacts;
    }

}
