<?php

class Ld_Services_Contacts_Tumblr extends Ld_Services_Contacts_Abstract
{

    public function getContacts($list = null, $normalised = true)
    {
        $contacts = array();

        $result = $this->request('http://api.tumblr.com/v2/user/following');
        foreach ($result['response']['blogs'] as $blog) {
            $id = $blog['name'];
            $contacts[$id] = $this->normaliseUser($blog);
            $contacts[$id]['following'] = true;
        }

        $result = $this->request('http://api.tumblr.com/v2/user/info', 'POST', array(), true);
        foreach ($result['response']['user']['blogs'] as $blog) {
            // if ($blog['primary'] != true) {
            //     continue;
            // }
            $pu = parse_url($blog['url']);
            $request = $this->request('http://api.tumblr.com/v2/blog/' . $pu['host'] . '/followers');
            foreach ($request['response']['users'] as $user) {
                $id = $user['name'];
                if (empty($contacts[$id])) {
                    $contacts[$id] = $this->normaliseUser($user);
                    $contacts[$id]['following'] = false;
                }
                $contacts[$id]['follower'] = true;
            }
        }

        return $contacts;
    }

    public function normaliseUser($tUser)
    {
        $user = array();
        $user['id'] = $tUser['name'];
        $user['guid'] = 'tumblr:' . $tUser['name'];
        $user['username'] = $tUser['name'];
        $user['url'] = $tUser['url'];
        $pu = parse_url($tUser['url']);
        $user['avatar_url'] = 'http://api.tumblr.com/v2/blog/' . $pu['host'] . '/avatar';
        return $user;
    }

    public function follow($id, $username)
    {
        foreach ($this->getContacts() as $contact) {
            if ($id == $contact['id']) {
                $params = array('url' => $contact['url']);
                $result = $this->request('http://api.tumblr.com/v2/user/follow', 'POST', $params);
                continue;
            }
        }
        // clear cache
        $this->getService()->cleanCache('http://api.tumblr.com/v2/user/following');
    }

}
