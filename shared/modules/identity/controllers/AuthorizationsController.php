<?php

require_once 'BaseController.php';

class Identity_AuthorizationsController extends Identity_BaseController
{

    public function indexAction()
    {
        if (!$user = Ld_Auth::getUser()) {
            return $this->disallow();
        }

        if (isset($_GET['revoke'])) {
            $tokenId = $_GET['revoke'];
            if ($token = $this->site->getModel('access_tokens')->get($tokenId)) {
                if ($token['user_id'] == $user['id']) {
                    $this->site->getModel('access_tokens')->delete($tokenId);
                }
            }
            return $this->redirectTo( $this->view->url() );
        }

        $this->view->applications = array();

        $access_tokens = $this->site->getModel('access_tokens')->searchByKey('user_id', $user['id']);
        foreach ($access_tokens as $id => $token) {
            $token['id'] = $id;
            $client_id = $token['client_id'];
            if ($client = $this->site->getModel('clients')->get($client_id)) {
                $this->view->applications[$client_id] = array(
                    'token' => $token,
                    'client' => $client
                );
            }
        }

        uasort($this->view->applications, array('self', "sortTokens"));

    }

    public function sortTokens($a, $b)
    {
        if (isset($a['token']['expires']) && isset($b['token']['expires'])) {
            return ($a['token']['expires'] > $b['token']['expires']) ? -1 : 1;
        } else if (isset($a['token']['expires'])) {
            return -1;
        }
        return 1;
    }

}
