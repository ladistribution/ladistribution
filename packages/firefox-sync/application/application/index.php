<?php
require_once dirname(__FILE__) . '/dist/config.php';
require_once dirname(__FILE__) . '/dist/prepend.php';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8"/>
<title><?php echo $application->getName() ?></title>

<?php if (defined('LD_COMPRESS_CSS') && constant('LD_COMPRESS_CSS')) : ?>
<link href="<?php echo Ld_Ui::getCssUrl('/h6e-minimal/h6e-minimal.compressed.css', 'h6e-minimal') ?>" rel="stylesheet" type="text/css"/>
<link href="<?php echo Ld_Ui::getCssUrl('/ld-ui/ld-ui.compressed.css', 'ld-ui') ?>" rel="stylesheet" type="text/css"/>
<?php else : ?>
<link href="<?php echo Ld_Ui::getCssUrl('/h6e-minimal/h6e-minimal.css', 'h6e-minimal') ?>" rel="stylesheet" type="text/css"/>
<link href="<?php echo Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui') ?>" rel="stylesheet" type="text/css"/>
<?php endif ?>
<?php if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) : ?>
<link href="<?php echo Ld_Ui::getApplicationStyleUrl() ?>" rel="stylesheet" type="text/css"/>
<?php endif ?>

<script type="text/javascript" src="<?php echo Ld_Ui::getJsUrl('/jquery/jquery.js', 'js-jquery') ?>"></script>
<script type="text/javascript" src="<?php echo Ld_Ui::getJsUrl('/ld/ld.js', 'lib-admin') ?>"></script>

<style type="text/css">
#sync-logo {
    height:95px;
    background:url(<?php echo $site->getPath() . '/' . $application->getPath() ?>/sync-logo.png) top center no-repeat;
    text-indent:-9999px;
}
.h6e-page-content {
    width:40em;
    margin:2em auto 0;
    padding:2em;
}
.h6e-simple-footer {
    width:44em;
    margin:2em auto 0;
}
.nowrap {
    white-space:nowrap;
}
</style>
</head>

<body class="ld-layout">

<?php Ld_Ui::topBar(); ?>

<div class="ld-main-content h6e-main-content">

  <?php // Ld_Ui::topNav(); ?>

  <div class="h6e-page-content h6e-block">

      <h1 id="sync-logo"><?php echo $application->getName() ?></h1>

      <div class="h6e-post-content">

          <?php if (Ld_Auth::isAuthenticated()) : ?>

          <h2>Infos</h2>

          <p>This Sync Server instance is:
              <strong class="nowrap">available for registered users only</strong>.</p>

          <p>The Custom Server URL is:
              <strong class="nowrap"><?php echo $serverUrl = Ld_Plugin::applyFilters('Weave:serverUrl', $application->getUrl()); ?></strong></p>

          <h2>Your data</h2>

          <?php $clients = ld_weave_count('clients'); if ($clients > 0) : ?>

              <?php
              $bookmarks = ld_weave_count('bookmarks');
              $forms = ld_weave_count('forms');
              $passwords = ld_weave_count('passwords');
              $history = ld_weave_count('history');
              ?>

              <p>You currently use <span class="nowrap"><strong><?php echo $clients ?></strong> clients</span> to store
                  <span class="nowrap"><strong><?php echo $bookmarks ?></strong> bookmarks</span>,
                  <span class="nowrap"><strong><?php echo $passwords ?></strong> passwords</span>,
                  <span class="nowrap"><strong><?php echo $forms ?></strong> forms entries</span> and
                  <span class="nowrap"><strong><?php echo $history ?></strong> pages</span> in your history.</p>

          <?php else : ?>

              <p>You don't have any data stored on this server yet.</p>

          <?php endif ?>

          <h2>Notes</h2>

          <p>Firefox Sync is directly integrated in Firefox since Firefox 4.</p>

          <p>To initialise it:
              <ol>
                  <li>Find the "<strong>Set Up Sync</strong>" menu entry</li>
                  <li>Choose "I already have a Firefox Sync account", "<strong>Connect</strong>"</li>
                  <li>Then "<strong>I don't have the device with me</strong>"</li>
                  <li>Enter your La Distribution username/password</li>
                  <li>Choose "<strong>Use a custom server</strong>", enter your server URL: "<strong><?php echo $serverUrl; ?></strong>"</li>
                  <li>Enter a new "<strong>Sync Key</strong>", it's like a password, you'll have to remember it</li>
              </ol>
          </p>

          <p>You can't register or change your password from Firefox.</p>

          <?php else : ?>

              <p>This application is for registered users only.</p>

          <?php endif ?>

      </div>

  </div>

  <div class="h6e-simple-footer" id="footer">
      Powered by <a href="http://mozillalabs.com/sync/">Firefox Sync</a> from <a href="http://mozillalabs.com/">Mozilla Labs</a>.
  </div>

</div>

<?php Ld_Ui::superBar(); ?>

</body>
</html>