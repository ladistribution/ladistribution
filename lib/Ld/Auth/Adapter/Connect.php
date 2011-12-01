<?php

class Ld_Auth_Adapter_Connect implements Zend_Auth_Adapter_Interface
{

    public function getSite()
    {
        return isset($this->_site) ? $this->_site : $this->_site = Zend_Registry::get('site');
    }

    public function getSession()
    {
        if (empty($this->_session)) {
            $this->_session = new Zend_Session_Namespace('Ld_Auth_Adapter_Connect');
        }
        return $this->_session;
    }

    public function setIdentityUrl($url)
    {
        $session = $this->getSession();
        $this->_identity = $session->identity = array('url' => $url);
        $pu = parse_url($url);
        $this->_host = $session->host = $pu['host'];
    }

    public function getHost()
    {
        $session = $this->getSession();
        if (empty($this->_host)) {
            if (isset($session->host)) {
                $this->_host = $session->host;
            } else {
                $pu = parse_url($session->identity['url']);
                $this->_host = $session->host = $pu['host'];
            }
        }
        return $this->_host;
    }

    public function setHost($host)
    {
        $session = $this->getSession();
        $this->_host = $session->host = $host;
    }

    public function getToken()
    {
        return $this->_token;
    }

    public function setToken($token)
    {
        $this->_token = $token;
    }

    public function isConnect()
    {
        try {
            $this->getOpenidConfiguration();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getOpenidConfiguration()
    {
        if (empty($this->_openidConfiguration)) {
            $this->_openidConfiguration = Ld_Http::jsonRequest('http://' . $this->getHost() . '/.well-known/openid-configuration');
        }
        return $this->_openidConfiguration;
    }

    public function getOauthKeys()
    {
        $host = $this->getHost();
        $keys = $this->getSite()->getConfig('oauth_keys', array());
        if (empty($keys[$host])) {
            $configuration = $this->getOpenidConfiguration();
            $params = array(
                'type' => 'client_associate',
                'application_name' => sprintf('La Distribution (%s)', $this->getSite()->getHost()),
                'application_url' => $this->getSite()->getUrl(),
                'application_type' => 'web',
                'redirect_uri' => $this->getSite()->getUrl()
            );
            $result = Ld_Http::jsonRequest($configuration['registration_endpoint'], 'POST', $params);
            $keys[$host] = array('client_id' => $result['client_id'], 'client_secret' => $result['client_secret']);
            $this->getSite()->setConfig('oauth_keys', $keys);
        }
        return $keys[$host];
    }

    public function getClientId()
    {
        $keys = $this->getOauthKeys();
        return $keys['client_id'];
    }

    public function getClientSecret()
    {
        $keys = $this->getOauthKeys();
        return $keys['client_secret'];
    }

    public function getCurrentUrl()
    {
        return Ld_Utils::getCurrentUrl();
    }

    public function getRedirectUrl()
    {
        if (empty($this->_redirectUrl)) {
            return $this->getSite()->getAdmin()->getLoginUrl() . '?ld_auth_action=connect';
        }
        return $this->_redirectUrl;
    }

    public function setRedirectUrl($redirectUrl)
    {
        return $this->_redirectUrl = $redirectUrl;
    }

    public function getLoginUrl()
    {
        $session = $this->getSession();
        if (empty($session->state)) {
            $session->state = Ld_Auth::generatePhrase(8);
        }
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->getClientId(),
            'scope' => 'openid profile email',
            'redirect_uri' => $this->getRedirectUrl(),
            'state' => $session->state
        );
        $configuration = $this->getOpenidConfiguration();
        $url = $configuration['authorization_endpoint'];
        $url .= '?' . http_build_query($params);
        return $url;
    }

    public function getAccessToken($code)
    {
        $configuration = $this->getOpenidConfiguration();
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getRedirectUrl(),
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
        );
        return Ld_Http::jsonRequest($configuration['token_endpoint'], 'POST', $params);
    }

    public function getUserinfo()
    {
        $configuration = $this->getOpenidConfiguration();
        $params = array('access_token' => $this->_token['access_token']);
        return Ld_Http::jsonRequest($configuration['user_info_endpoint'], 'POST', $params);
    }

    public function getAuthResult()
    {
        $session = $this->getSession();

        // Check state
        if ($session->state == $_GET['state']) {
            // Ok
            unset($session->state);
        } else {
            // Do Nothing
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
        }

        // Get Token
        $token = $this->getAccessToken($_GET['code']);
        $this->setToken($token);

        $identity = $this->_identity = $this->getUserinfo();
        if (empty($identity)) {
            // if authentication fails ...
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
        }

        // Identity match
        if (isset($session->identity)) {
            if ($identity['url'] == $session->identity['url']) {
                unset($session->host);
                unset($session->identity);
                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity['url']);
            } else if (isset($identity['url_alias']) && in_array($session->identity['url'], $identity['url_alias'])) {
                unset($session->host);
                unset($session->identity);
                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $session->identity['url']);
            } else {
                unset($session->host);
                unset($session->identity);
                return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS, null);
            }
        } else {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity['url']);
        }
    }

    public function callback()
    {
        $result = $this->getAuthResult();

        $identity = $this->_identity;

        if ($result->isValid()) {
            $users = $this->getSite()->getModel('users');
            if (!$exists = $users->getUserByUrl($identity['url'])) {
                $this->createUser($identity);
            }
        }

        return $result;
    }

    public function redirect()
    {
        $loginUrl = $this->getLoginUrl();
        header("Location:" . $loginUrl);
        exit;
    }

    public function authenticate()
    {
        // Callback
        if (isset($_GET['code']) && isset($_GET['state'])) {
            return $this->callback();
        }

        // Redirect. Only trigger this if an identity is set
        if (isset($this->_identity) && $this->isConnect()) {
            return $this->redirect();
        }

        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
    }

    public function createUser($userinfo)
    {
        $namespace = $this->getHost();

        $user = array(
            'origin'     => 'Ld_Auth_Adapter_Connect',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'addr'       => $_SERVER['REMOTE_ADDR'],
            'fullname'   => $userinfo['fullname'],
            'username'   => $userinfo['username'],
            'email'      => $userinfo['email'],
            'hash'       => '',
            'identities' => array($namespace => array(
                'id'       => $userinfo['id'],
                'url'      => $userinfo['url'],
                'username' => $userinfo['username'],
                'fullname' => $userinfo['fullname'],
                'email'    => $userinfo['email'],
                'token'    => $this->getToken(),
                'verified' => true
            ))
        );
        // model
        $users = $this->getSite()->getModel('users');
        // username already registered
        if ($exists = $users->getUserBy('username', $user['username'])) {
            // namespaced username
            $user['username'] = 'acct:' . $userinfo['username'] . '@' . $namespace;
        }
        return $users->addUser($user);
    }

}
