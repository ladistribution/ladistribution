<?php
require_once dirname(__FILE__) . '/dist/config.php';
require_once dirname(__FILE__) . '/dist/prepend.php';
require_once dirname(__FILE__) . '/functions.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo Zend_Registry::get('instance')->getName() ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link href="<?php echo weave_get_css_url('/h6e-minimal/h6e-minimal.css', 'h6e-minimal') ?>" media="screen" rel="stylesheet" type="text/css"/>
<link href="<?php echo weave_get_css_url('/ld-ui/ld-ui.css', 'ld-ui') ?>" media="screen" rel="stylesheet" type="text/css"/>
<style type="text/css">
#weave-logo {
    width:350px;
    height:146px;
    background:url(<?php echo Zend_Registry::get('instance')->getUrl() ?>logo.png) top left no-repeat;
    text-indent:-9999px;
    float:left;
}
.h6e-post-content {
    width:610px;
    float:left;
    margin-top:1em;
    margin-bottom:2em;
}
</style>
</head>

<body class="ld-layout">

<?php Ld_Ui::top_bar(); ?>

<div class="h6e-main-content">

  <div class="h6e-page-content">

      <h1 id="weave-logo"><?php echo Zend_Registry::get('instance')->getName() ?></h1>

      <div class="h6e-post-content">

          <?php if (Ld_Auth::isAuthenticated()) : ?>

          <h2>Infos</h2>

          <p>Your Custom Server URL is: <strong><?php echo Zend_Registry::get('instance')->getUrl(); ?></strong></p>

          <p>This Weave instance is: <strong>available for registered users only</strong>.</p>

          <h2>Notes</h2>
          <ul>
              <li>registration is not possible from Weave client</li>
              <li>password change is not possible from Weave client</li>
              <li>Mozilla recommend to use Weave with https</li>
          </ul>

          <?php else : ?>

              <p>This application is for registered users only.</p>

          <?php endif ?>

      </div>

  </div>

  <div class="h6e-simple-footer" id="footer">
      Powered by <a href="http://mozillalabs.com/weave/">Weave</a> from <a href="http://mozillalabs.com/">Mozilla Labs</a>.
  </div>

</div>

<?php Ld_Ui::super_bar(); ?>

</body>
</html>