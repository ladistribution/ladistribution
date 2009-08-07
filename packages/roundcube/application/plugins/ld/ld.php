<?php

class ld extends rcube_plugin
{

  function init()
  {
      $this->register_handler('plugin.superbar', array($this, 'generate_super_bar'));
      
      // $this->add_hook('startup', array($this, 'startup'));
      // $this->add_hook('authenticate', array($this, 'authenticate'));
  }

  function generate_super_bar()
  {
      Ld_Ui::superBar(array('style' => true));
      return '<style type="text/css">#mainscreen { margin-bottom:35px; } </style>';
  }

  function startup($args)
  {
      $this->user = Ld_Auth::getUser();
      if (isset($this->user)) {
          if ($args['task'] == 'mail' && empty($args['action']) && empty($_SESSION['user_id'])) {
              $args['action'] = 'login';
          }
      } else {
          global $RCMAIL;
          $RCMAIL->kill_session();
      }
      return $args;
  }

  function authenticate($args)
  {
      if (isset($this->user)) {
          $args['user'] = $this->user['email'];
          $args['pass'] = '';
          if (strpos($this->user['email'], 'gmail.com')) {
              $args['host'] = 'ssl://imap.gmail.com/';
          }
      }
      return $args;
  }

}
