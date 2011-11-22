<?php

class Api_MetaController extends Ld_Controller_Action
{

    function hostmetaAction()
    {
        $lrddUrl = $this->admin->buildUrl(array('module' => 'api', 'controller' => 'meta', 'action' => 'webfinger'));
        $this->noRender();
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
        <XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0' xmlns:hm='http://host-meta.net/xrd/1.0'>
            <hm:Host xmlns='http://host-meta.net/xrd/1.0'><?php echo $this->site->getHost() ?></hm:Host>
            <Link rel='lrdd' template='<?php echo $lrddUrl ?>?q={uri}' type="application/xrd+xml"/>
        </XRD>
        <?php
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
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: text/xml");
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

}
