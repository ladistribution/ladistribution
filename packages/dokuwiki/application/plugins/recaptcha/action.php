<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl2.html)
 * @author     Adrian Schlegel <adrian.schlegel@liip.ch>
 * @author     Robert Bronsdon <reashlin@gmail.com>
 * @author     François Hodierne <francois@hodierne.net>
 */

class action_plugin_recaptcha extends DokuWiki_Action_Plugin {

    /**
     * get plugin info
     *
     */
    function getInfo()
    {
        return array(
            'author' => 'h6e.net',
            'email'  => 'contact@h6e.net',
            'date'   => '2010-04-28',
            'name'   => 'La Distribution Recaptcha',
            'desc'   => 'integrate Recaptcha initialised in La Distribution',
            'url'    => 'http://h6e.net/dokuwiki/plugins/ld-recaptcha',
        );
    }

    /**
     * register an event hook
     *
     */
    function register(&$controller)
    {
        global $ld_recaptcha_service;
        if ($ld_recaptcha_service) {
            $controller->register_hook('ACTION_ACT_PREPROCESS',
                'BEFORE',
                $this,
                'preprocess',
                array());
            $controller->register_hook('HTML_REGISTERFORM_OUTPUT',
                'BEFORE',
                $this,
                'insert',
                array());
        }
    }

    /**
     * insert html code for recaptcha into the form
     *
     * @param obj $event
     * @param array $param
     */
    function insert(&$event, $param)
    {
        global $ld_recaptcha_service;
        if ($ld_recaptcha_service) {
            $recaptcha  = '<style type="text/css">';
            $recaptcha .= '#recaptcha_widget_div { width:470px ;margin:auto; }';
            $recaptcha .= '</style>';
            $recaptcha .= '<label class="block"><span>' . Ld_Translate::translate('Are you human?') . '</span>';
            $recaptcha .= $ld_recaptcha_service->getHTML();
            $pos = $event->data->findElementByAttribute('type','submit');
            $event->data->insertElement($pos++, $recaptcha);
        }

    }

    /**
     * process the answer to the captcha
     *
     * @param obj $event
     * @param array $param
     *
     */
    function preprocess(&$event, $param)
    {
        global $ld_recaptcha_service;

        // Check we are either registering or saving a html page
        if ($ld_recaptcha_service && $event->data == 'register' && $_POST['save']) {

            // Check the recaptcha answer and only submit if correct
            try {
                $result = $ld_recaptcha_service->verify($_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
            } catch (Exception $e) {
                msg($e->getMessage() . ' (reCAPTCHA)', -1);
                $_POST['save'] = false;
                return;
            }
            if (!$result->isValid()) {
                msg(Ld_Translate::translate('The reCAPTCHA response was incorrect.'), -1);
                $_POST['save'] = false;
            }

        }
    }

} //end of action class
