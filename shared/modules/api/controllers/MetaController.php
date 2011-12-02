<?php

class Api_MetaController extends Ld_Controller_Action
{

    function hostMetaAction()
    {
        $this->noRender();
        $this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHeader('Content-Type', 'text/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
        <XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0' xmlns:hm='http://host-meta.net/xrd/1.0'>
            <hm:Host xmlns='http://host-meta.net/xrd/1.0'><?php echo $this->site->getHost() ?></hm:Host>
            <Link rel='lrdd' template='<?php echo $this->_getSecureUrl('webfinger') ?>?q={uri}' type="application/xrd+xml"/>
        </XRD>
        <?php
    }

    function openidConfigurationAction()
    {
        $configuration = array(
            'site_name' => $this->site->getName(),
            'site_url' => $this->site->getAbsoluteUrl(),
            'version' => '3.0',
            'registration_endpoint' => $this->_getSecureUrl('register', 'oauth'),
            'authorization_endpoint' => $this->_getSecureUrl('authorize', 'oauth'),
            'token_endpoint' => $this->_getSecureUrl('token', 'oauth'),
            'user_info_endpoint' => $this->_getSecureUrl('userinfo', 'oauth'),
            'scopes_supported' => array('openid', 'profile', 'email'),
            'flows_supported' => array('code')
        );
        $this->noRender();
        $this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(Zend_Json::encode($configuration));
    }

    function webfingerAction()
    {
        $q = $this->_getParam('q');
        if (strpos($q, 'acct:') !== false) {
            $q = str_replace('acct:', '', $q);
        }
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($q)) {
            $user = $this->site->getModel('users')->getUserBy('email', $q);
        }
        if (empty($user)) {
            $host = $this->site->getHost();
            if (strpos($q, '@' . $host)) {
                $username = str_replace('@' . $host, '', $q);
                $user = $this->site->getModel('users')->getUserBy('username', $username);
            }
        }
        $this->noRender();
        $this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHeader('Content-Type', 'text/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
        <XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0'>
            <Subject>acct:<?php echo $q ?></Subject>
            <?php if (isset($user)) : $identityUrl = $this->admin->getIdentityUrl($user['username']); ?>
            <Alias><?php echo $identityUrl ?></Alias>
            <Link rel='http://webfinger.net/rel/profile-page' href='<?php echo $identityUrl ?>'/>
            <Link rel='http://specs.openid.net/auth/2.0/provider' href='<?php echo $identityUrl ?>'/>
            <?php
            if ($unhosted = $this->site->getApplication('unhosted')) {
                echo "<Link rel='http://unhosted.org/spec/dav/0.1' href='" . $unhosted->getUrl() . "'/>\n";
            }
            ?>
            <?php endif ?>
        </XRD>
        <?php
    }

    protected function _getUrl($action, $controller = 'meta', $module = 'api')
    {
        return $this->admin->buildAbsoluteUrl(array('module' => $module, 'controller' => $controller, 'action' => $action));
    }

    protected function _getSecureUrl($action, $controller = 'meta', $module = 'api')
    {
        return $this->admin->buildAbsoluteSecureUrl(array('module' => $module, 'controller' => $controller, 'action' => $action));
    }

}
