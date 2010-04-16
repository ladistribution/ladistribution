<?php
require_once dirname(__FILE__) . '/dist/config.php';
require_once dirname(__FILE__) . '/dist/prepend.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo Zend_Registry::get('instance')->getName() ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link href="<?php echo Ld_Ui::getCssUrl('/h6e-minimal/h6e-minimal.css', 'h6e-minimal') ?>"
    media="screen" rel="stylesheet" type="text/css"/>
<link href="<?php echo Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') ?>"
    media="screen" rel="stylesheet" type="text/css"/>
<style type="text/css">
#weave-logo {
    height:131px;
    background:url(<?php echo Zend_Registry::get('instance')->getUrl() ?>logo.png) top center no-repeat;
    text-indent:-9999px;
}
.h6e-main-content {
    width:35em;
}
.nowrap {
    white-space:nowrap;
}
</style>
</head>

<body class="ld-layout">

<?php Ld_Ui::topBar(); ?>

<div class="h6e-main-content">

  <div class="h6e-page-content">

      <h1 id="weave-logo"><?php echo Zend_Registry::get('instance')->getName() ?></h1>

      <div class="h6e-post-content">

          <?php if (Ld_Auth::isAuthenticated()) : ?>

          <h2>Infos</h2>

          <p>Your Custom Server URL is: <strong><?php echo Zend_Registry::get('instance')->getUrl(); ?></strong></p>

          <p>This Weave instance is: <strong>available for registered users only</strong>.</p>

          <h2>Your datas</h2>

          <?php $clients = ld_weave_count('clients'); if ($clients > 0) : ?>

              <?php
              $bookmarks = ld_weave_count('bookmarks');
              $forms = ld_weave_count('forms');
              $passwords = ld_weave_count('passwords');
              $history = ld_weave_count('history');
              ?>

              <p>You currently use <span class="nowrap"><strong><?php echo $clients ?></strong> weave clients</span> to store
                  <span class="nowrap"><strong><?php echo $bookmarks ?></strong> bookmarks</span>,
                  <span class="nowrap"><strong><?php echo $passwords ?></strong> passwords</span>,
                  <span class="nowrap"><strong><?php echo $forms ?></strong> forms entries</span> and
                  <span class="nowrap"><strong><?php echo $history ?></strong> pages</span> in your history.</p>

          <?php else : ?>

              <p>You don't have any Weave datas stored on this server yet.</p>

          <?php endif ?>

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

<?php Ld_Ui::superBar(); ?>

</body>
</html>