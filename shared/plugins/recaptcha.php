<?php

class Ld_Plugin_Recaptcha
{

    public static function infos()
    {
        return array(
            'name' => 'reCAPTCHA',
            'url' => 'http://ladistribution.net/wiki/plugins/#recaptcha',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0.2',
            'description' => 'Integrate reCAPTCHA in registration forms.',
            'license' => 'MIT / GPL'
        );
    }

    public static function load()
    {
        Ld_Plugin::addFilter('Slotter:preferences', array('Ld_Plugin_Recaptcha', 'slotter_preferences'));
        Ld_Plugin::addAction('Auth:register:form', array('Ld_Plugin_Recaptcha', 'auth_register_form'));
        Ld_Plugin::addAction('Auth:register:validate', array('Ld_Plugin_Recaptcha', 'auth_register_validate'));
        Ld_Plugin::addAction('Wordpress:plugin', array('Ld_Plugin_Recaptcha', 'wordpress_init'));
        Ld_Plugin::addAction('Bbpress:plugin', array('Ld_Plugin_Recaptcha', 'bbpress_init'));
    }

    public static $recaptchaService = null;

    public static function getService()
    {
        if (empty(self::$recaptchaService)) {
            $site = Zend_Registry::get('site');
            $recaptcha_public_key = $site->getConfig('recaptcha_public_key');
            $recaptcha_private_key = $site->getConfig('recaptcha_private_key');
            if (empty($recaptcha_public_key) || empty($recaptcha_private_key)) {
                return null;
            }
            self::$recaptchaService = new Zend_Service_ReCaptcha($recaptcha_public_key, $recaptcha_private_key);
            self::$recaptchaService->setOptions(array('theme' => 'clean'));
        }
        return self::$recaptchaService;
    }

    public static function slotter_preferences($preferences)
    {
        $preferences[] = array(
            'name' => 'recaptcha_public_key', 'type' => 'text', 'label' => 'reCAPTCHA public key'
        );
        $preferences[] = array(
            'name' => 'recaptcha_private_key', 'type' => 'text', 'label' => 'reCAPTCHA private key'
        );
        return $preferences;
    }

    public static function auth_register_form()
    {
        $recaptcha = self::getService();
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
        <label for="recaptcha_response_field">Are you human?</label>
        <div style="height:120px"><?php echo $recaptcha->getHTML(); ?></div>
        <?php
    }

    public static function auth_register_validate($params = array())
    {
        if (!$recaptcha = self::getService()) {
            return;
        }
        try {
            $result = $recaptcha->verify($params['recaptcha_challenge_field'], $params['recaptcha_response_field']);
        } catch (Exception $e) {
            return $e
            ->getMessage() . ' (reCAPTCHA)';
        }
        if (!$result->isValid()) {
            throw new Exception('reCAPTCHA validation failed. Try again please.');
        }
    }

    // Wordpress
    // inspired from: http://www.blaenkdenum.com/wp-recaptcha/

    public static function wordpress_init()
    {
        add_action('register_form', array('Ld_Plugin_Recaptcha', 'wordpress_display'));
        add_filter('registration_errors', array('Ld_Plugin_Recaptcha', 'wordpress_check'));
    }

    public static function wordpress_display()
    {
        if (!$recaptcha = self::getService()) {
            return;
        }
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

    public static function wordpress_check($errors)
    {
        if (!$recaptcha = self::getService()) {
            return $errors;
        }
        try {
            $result = $recaptcha->verify($_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
            if (!$result->isValid()) {
                $errors->add('captcha_wrong', __('<strong>ERROR</strong>: The reCAPTCHA response was incorrect.'));
            }
        } catch (Exception $error) {
            $errors->add('captcha_wrong', '<strong>ERROR</strong>: ' . $error->getMessage() . '. (reCAPTCHA)');
        }
        return $errors;
    }

    // bbPress
    // derivated from: http://www.gospelrhys.co.uk/plugins/bbpress-plugins/recaptcha-bbpress-plugin

    public static function bbpress_init()
    {
        add_action('extra_profile_info', array('Ld_Plugin_Recaptcha', 'bbpress_registration_add_field'), 11);
        add_filter('sanitize_user', array('Ld_Plugin_Recaptcha', 'bbpress_verify'));
    }

    public static function bbpress_register_page() 
    {	
        foreach (array($_SERVER['PHP_SELF'], $_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME']) as $page) {
            if (strpos($page, '.php') !== false) 
                $file = $page;
        }
        return (bb_find_filename($file) == "register.php");
    }

    public static function bbpress_registration_add_field()
    {
        global $bb_register_error;
        if (self::bbpress_register_page() && $recaptcha = self::getService()) {
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
                        <label for="recaptcha_response_field">Are you human?</label>
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

    public static function bbpress_verify($param_filter = null)
    {
        global $bb_register_error, $bad_input;
        if (!$recaptcha = self::getService()) {
            return $param_filter_1;
        }
        if (self::bbpress_register_page()) {
            try {
                $result = $recaptcha->verify($_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
                if (!$result->isValid()) {
                    $bad_input = true;
                    $bb_register_error->add( 'recaptcha', __('The reCAPTCHA response was incorrect.') );
                }
            } catch (Exception $error) {
                $bad_input = true;
                $bb_register_error->add( 'recaptcha', $error->getMessage() );
            }
        }
        return $param_filter;
    }

}
