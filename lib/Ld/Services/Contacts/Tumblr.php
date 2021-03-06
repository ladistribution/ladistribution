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
        }

        $result = $this->request('http://api.tumblr.com/v2/user/info', 'POST');
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
                }
            }
        }

        return $contacts;
    }

    public function normaliseUser($tUser)
    {
        $user = array();
        $user['guid'] = 'tumblr:' . $tUser['name'];
        $user['username'] = $tUser['name'];
        $user['url'] = $tUser['url'];
        $pu = parse_url($tUser['url']);
        $user['avatar_url'] = 'http://api.tumblr.com/v2/blog/' . $pu['host'] . '/avatar';
        return $user;
    }

}
