<?php

// Ld

function ld_recaptcha_get_service()
{
    $site = Zend_Registry::get('site');
    $recaptcha_public_key = $site->getConfig('recaptcha_public_key');
    $recaptcha_private_key = $site->getConfig('recaptcha_private_key');
    if (empty($recaptcha_public_key) || empty($recaptcha_private_key)) {
        return null;
    }
    $recaptcha = new Zend_Service_ReCaptcha($recaptcha_public_key, $recaptcha_private_key);
    return $recaptcha;
}

function ld_recaptcha_settings($preferences)
{
    $preferences[] = array(
        'name' => 'recaptcha_public_key', 'type' => 'text', 'label' => 'reCAPTCHA public key'
    );
    $preferences[] = array(
        'name' => 'recaptcha_private_key', 'type' => 'text', 'label' => 'reCAPTCHA private key'
    );
    return $preferences;
}

Ld_Plugin::addFilter('Slotter:preferences', 'ld_recaptcha_settings');

function ld_recaptcha_form()
{
    $recaptcha = ld_recaptcha_get_service();
    if (empty($recaptcha)) {
        return;
    }
    $recaptcha->setOptions(array('theme' => 'clean'));
    ?>
    <style type="text/css">
    #ld-login-box { width:465px; }
    #ld-login-box #recaptcha_response_field { border:1px solid #BBBBBB !important; font-size: 1.1em !important; }
    #ld-login-box #recaptcha_response_field:focus { border:1px solid #666 !important; }
    #ld-login-box .recaptchatable { border:1px solid #BBBBBB !important; }
    </style>
    <label for="recaptcha_response_field">Are you human?</label>
    <?php
    echo $recaptcha->getHTML();
}

Ld_Plugin::addFilter('Auth:register:form', 'ld_recaptcha_form');

function ld_recaptcha_validate($validate = true, $params = array())
{
    $recaptcha = ld_recaptcha_get_service();
    if (empty($recaptcha)) {
        return $validate;
    }
    try {
        $recaptcha = new Zend_Service_ReCaptcha($recaptcha_public_key, $recaptcha_private_key);
        $result = $recaptcha->verify($params['recaptcha_challenge_field'],$params['recaptcha_response_field']);
        if (!$result->isValid()) {
            return 'Validation failed. Try again please. (reCAPTCHA)';
        }
    } catch (Exception $error) {
        return $error->getMessage() . ' (reCAPTCHA)';
    }
    return $validate;
}

Ld_Plugin::addFilter('Auth:register:validate', 'ld_recaptcha_validate', 10, 2);

// WordPress

function ld_wordpress_recaptcha_init()
{
    add_action('register_form', 'ld_wordpress_recaptcha_display');
    add_filter('registration_errors', 'ld_wordpress_recaptcha_check');
}

Ld_Plugin::addAction('Wordpress:plugin', 'ld_wordpress_recaptcha_init');

function ld_wordpress_recaptcha_display()
{
    $recaptcha = ld_recaptcha_get_service();
    if (empty($recaptcha)) {
        return;
    }
    $recaptcha->setOptions(array('theme' => 'clean'));
    ?>
    <style type="text/css">
    #login { width:490px; }
    #login h1 a { width:490px; margin: auto; }
    #login #recaptcha_response_field { border:1px solid #E5E5E5 !important; font-size: 18px !important; color: #555555 }
    #login .recaptchatable { border:1px solid #E5E5E5 !important; background: #FBFBFB !important; }
    </style>
    <label for="recaptcha_response_field">Are you human?</label>
    <?php echo $recaptcha->getHTML(); ?>
    <br/>
    <?php
}

function ld_wordpress_recaptcha_check($errors)
{
    try {
        $recaptcha = ld_recaptcha_get_service();
        if (empty($recaptcha)) {
            return $errors;
        }
        $result = $recaptcha->verify($_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
        if (!$result->isValid()) {
            $errors->add('captcha_wrong', __('<strong>ERROR</strong>: The reCAPTCHA response was incorrect.'));
        }
    } catch (Exception $error) {
        $errors->add('captcha_wrong', '<strong>ERROR</strong>: ' . $error->getMessage() . '. (reCAPTCHA)');
    }
    return $errors;
}
