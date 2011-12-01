<?php

class Ld_Auth_Adapter_Facebook implements Zend_Auth_Adapter_Interface
{

    public function getFacebook()
    {
        if (empty($this->_facebook)) {
            require_once 'Ld/Services/Facebook.php';
            $this->_facebook = new Ld_Services_Facebook();
        }
        return $this->_facebook;
    }

    public function getSession()
    {
        if (empty($this->_session)) {
            $this->_session = new Zend_Session_Namespace('Ld_Auth_Adapter_Facebook');
        }
        return $this->_session;
    }

    public function setIdentityUrl($url)
    {
        $session = $this->getSession();
        $session->identity = array('url' => $url);
    }

    public function isActive()
    {
        $session = $this->getSession();
        return isset($session->identity);
    }

    public function getCallbackUrl()
    {
        return $this->_callbackUrl;
    }

    public function setCallbackUrl($url)
    {
        $this->_callbackUrl = $url;
    }

    public function authenticate()
    {
        $facebook = $this->getFacebook();
        $session = $this->getSession();

        // Callback
        if (isset($_GET['code']) && isset($_GET['state']) && $this->isActive()) {
            $identity = $facebook->getIdentity();
            if (empty($identity)) {
                // if facebook authentication fails ...
                return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
            }
            // Identity match
            if ($identity['url'] == $session->identity['url']) {
                $result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity['url']);
            } else if (isset($identity['url_alias']) && in_array($session->identity['url'], $identity['url_alias'])) {
                $result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $session->identity['url']);
            } else {
                $result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS, null);
            }
            unset($session->identity);
            return $result;
        }

        // Only trigger this if an identity is set
        if (isset($session->identity)) {
            $callbackUrl = $this->getCallbackUrl();
            $loginUrl = $facebook->getLoginUrl($callbackUrl);
            header("Location:" . $loginUrl);
            exit;
        }
    }

}
