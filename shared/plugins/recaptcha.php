<?php

class Ld_Plugin_Recaptcha
{

    public function infos()
    {
        return array(
            'name' => 'reCAPTCHA',
            'url' => 'http://ladistribution.net/wiki/plugins/#recaptcha',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.3',
            'description' => Ld_Translate::translate('Integrate reCAPTCHA to registration forms.'),
            'license' => 'MIT / GPL'
        );
    }

    const STATUS_OK = 1;
    const STATUS_ERROR = 0;

    public function status()
    {
        $recaptcha = $this->getService();
        if (empty($recaptcha)) {
            return array(self::STATUS_ERROR, sprintf(Ld_Translate::translate('%s is not running. Check your configuration to enable it.'), 'reCAPTCHA'));
        }
        // would be cool to check api key validity here
        return array(self::STATUS_OK, sprintf(Ld_Translate::translate('%s is configured and running.'), 'reCAPTCHA'));
    }

    public function load()
    {
        Ld_Plugin::addAction('Auth:register:form', array($this, 'auth_register_form'));
        Ld_Plugin::addAction('Auth:register:validate', array($this, 'auth_register_validate'));
        Ld_Plugin::addAction('Wordpress:plugin', array($this, 'wordpress_init'));
        Ld_Plugin::addAction('Bbpress:plugin', array($this, 'bbpress_init'));
        Ld_Plugin::addAction('Dokuwiki:plugin', array($this, 'dokuwiki_init'));
    }

    public $recaptchaService = null;

    public function getService()
    {
        if (empty($this->recaptchaService)) {
            $site = Zend_Registry::get('site');
            $recaptcha_public_key = $site->getConfig('recaptcha_public_key');
            $recaptcha_private_key = $site->getConfig('recaptcha_private_key');
            if (empty($recaptcha_public_key) || empty($recaptcha_private_key)) {
                return null;
            }
            $this->recaptchaService = new Zend_Service_ReCaptcha($recaptcha_public_key, $recaptcha_private_key);
            $this->recaptchaService->setOptions(array('theme' => 'clean'));
        }
        return $this->recaptchaService;
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'recaptcha_public_key', 'type' => 'text', 'label' => Ld_Translate::translate('reCAPTCHA Public key')
        );
        $preferences[] = array(
            'name' => 'recaptcha_private_key', 'type' => 'text', 'label' => Ld_Translate::translate('reCAPTCHA Private key')
        );
        return $preferences;
    }

    public function auth_register_form()
    {
        $recaptcha = $this->getService();
        if (empty($recaptcha)) {
            return;
        }
        ?>
        <style type="text/css">
        #ld-login-box { width:465px; }
        #ld-login-box #recaptcha_response_field { border:1px solid #BBBBBB !important; font-size: 1.1em !important; }
        #ld-login-box #recaptcha_response_field:focus { border:1px solid #666 !important; }
        #ld-login-box .recaptchatable { border:1px solid #BBBBBB !important; background:white !important; }
        </style>
        <label for="recaptcha_response_field"><?php echo Ld_Translate::translate('Are you human?') ?></label>
        <div style="height:120px"><?php echo $recaptcha->getHTML(); ?></div>
        <?php
    }

    public function auth_register_validate($params = array())
    {
        if (!$recaptcha = $this->getService()) {
            return;
        }
        try {
            $result = $recaptcha->verify($params['recaptcha_challenge_field'], $params['recaptcha_response_field']);
        } catch (Exception $e) {
            return $e->getMessage() . ' (reCAPTCHA)';
        }
        if (!$result->isValid()) {
            throw new Exception('reCAPTCHA validation failed. Try again please.');
        }
    }

    // Wordpress
    // inspired from: http://www.blaenkdenum.com/wp-recaptcha/

    public function wordpress_init()
    {
        add_action('register_form', array($this, 'wordpress_display'));
        add_filter('registration_errors', array($this, 'wordpress_check'));
    }

    public function wordpress_display()
    {
        if (!$recaptcha = $this->getService()) {
            return;
        }
        ?>
        <style type="text/css">
        #login { width:490px; }
        #login h1 a { width:490px; margin: auto; }
        #login #recaptcha_response_field { border:1px solid #E5E5E5 !important; font-size: 18px !important; color: #555555 }
        #login .recaptchatable { border:1px solid #E5E5E5 !important; background: #FBFBFB !important; }
        </style>
        <label for="recaptcha_response_field"><?php echo Ld_Translate::translate('Are you human?') ?></label>
        <?php echo $recaptcha->getHTML(); ?>
        <br/>
        <?php
    }

    public function wordpress_check($errors)
    {
        if (!$recaptcha = $this->getService()) {
            return $errors;
        }
        try {
            $result = $recaptcha->verify($_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
            if (!$result->isValid()) {
                $errors->add('captcha_wrong', '<strong>ERROR</strong>: ' . Ld_Translate::translate('The reCAPTCHA response was incorrect.') );
            }
        } catch (Exception $error) {
            $errors->add('captcha_wrong', '<strong>ERROR</strong>: ' . $error->getMessage() . '. (reCAPTCHA)');
        }
        return $errors;
    }

    // bbPress
    // derivated from: http://www.gospelrhys.co.uk/plugins/bbpress-plugins/recaptcha-bbpress-plugin

    public function bbpress_init()
    {
        add_action('extra_profile_info', array($this, 'bbpress_registration_add_field'), 11);
        add_filter('sanitize_user', array($this, 'bbpress_verify'));
    }

    public function bbpress_register_page()
    {
        foreach (array($_SERVER['PHP_SELF'], $_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME']) as $page) {
            if (strpos($page, '.php') !== false)
                $file = $page;
        }
        return (bb_find_filename($file) == "register.php");
    }

    public function bbpress_registration_add_field()
    {
        global $bb_register_error;
        if ($this->bbpress_register_page() && $recaptcha = $this->getService()) {
            $class = 'form-field';
            if ( $profile_info_key_error = $bb_register_error->get_error_message( $key ) )
                $class .= ' form-invalid error';
            ?>
            <style type="text/css">
            .recaptchatable { background: #FBFBFB !important; }
            </style>
            <fieldset>
            <legend>reCAPTCHA</legend>
            <?php if ( isset($profile_info_key_error) )
                echo "<p style='color:red;font-weight:bold;font-size:1.4em'>$profile_info_key_error</p>"; ?>
            <table width="100%">
                <tr class="<?php echo $class ?>">
                    <th scope="row">
                        <label for="recaptcha_response_field"><?php echo Ld_Translate::translate('Are you human?') ?></label>
                    </th>
                    <td>
                        <?php echo $recaptcha->getHTML(); ?>
                    </td>
                </tr>
            </table>
            </fieldset>
            <?php
        }
    }

    public function bbpress_verify($param_filter = null)
    {
        global $bb_register_error, $bad_input;
        if (!$recaptcha = $this->getService()) {
            return $param_filter_1;
        }
        if ($this->bbpress_register_page()) {
            try {
                $result = $recaptcha->verify($_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
                if (!$result->isValid()) {
                    $bad_input = true;
                    $bb_register_error->add( 'recaptcha', Ld_Translate::translate('The reCAPTCHA response was incorrect.') );
                }
            } catch (Exception $error) {
                $bad_input = true;
                $bb_register_error->add( 'recaptcha', $error->getMessage() );
            }
        }
        return $param_filter;
    }

    /* Dokuwiki */

    public function dokuwiki_init()
    {
        global $ld_recaptcha_service;
        $ld_recaptcha_service = $this->getService();
    }

}
