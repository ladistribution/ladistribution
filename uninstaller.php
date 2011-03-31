<?php

// Default Configuration

defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.net/');

defined('LD_DEBUG') OR define('LD_DEBUG', false);

function out($message, $class = 'ok')
{
    if (defined('LD_CLI') && constant('LD_CLI')) {
        fwrite(STDOUT, "# $message");
        fwrite(STDOUT, PHP_EOL);
    } else {
        echo '<li class="' . $class . '">' . $message . "</li>\n";
        flush();
    }
}

function error($message)
{
    out($message, 'error');
    ?>
    </ul>
    <form method="post" action="">
        <input type="submit" class="submit button ld-button" name="uninstall" value="Try Again">
    </form>
    </div></div></div>
    </body></html>
    <?php
    exit;
}

?><!DOCTYPE html>
<html>
<head>
    <title>La Distribution Uninstaller</title>
    <meta charset="utf-8">
    <link href="<?php echo LD_SERVER ?>css/h6e-minimal/h6e-minimal.css?v=0.2-10" rel="stylesheet" type="text/css">
    <link href="<?php echo LD_SERVER ?>css/ld-ui/ld-ui.css?v=0.5-82" rel="stylesheet" type="text/css">
    <style type="text/css">
    .h6e-page-title { background:url("http://ladistribution.net/logo.png") no-repeat top center; height:75px; text-indent:-9999px; }
    .h6e-page-content { position:relative; }
    .h6e-main-content { width:40em; }
    .h6e-post-content { padding-bottom:90px; }
    .h6e-simple-footer { position:absolute; bottom:0; width:40em; }
    ul.ld-steps { margin:25px 0; list-style-type:none; }
    ul.ld-steps li { margin:10px 0; padding-left:25px; background:no-repeat 0 3px; }
    ul.ld-steps li.ok { background-image:url("<?php echo LD_SERVER ?>css/ld-ui/iconic/check_16x13.png"); }
    ul.ld-steps li.error { background-image:url("<?php echo LD_SERVER ?>css/ld-ui/iconic/x_alt_16x16.png"); }
    </style>
</head>
<body>
  <div class="ld-main-content h6e-main-content">
      <div class="h6e-page-content">
          <h1 class="h6e-page-title">La Distribution Uninstaller</h1>
          <div class="h6e-simple-footer" >
              Powered by <a href="http://ladistribution.net/">La Distribution</a>,
              a community project initiated by <a href="http://h6e.net/">h6e</a>.
          </div>
          <div class="h6e-post-content">
              <p>Thank you for downloading this uninstaller.</p>
              <p>It will help you uninstall La Distribution in <strong>less than a minute</strong>.</p>
              <p>If you encouter any problem, please visit <a href="http://ladistribution.net/en/forums/">our forums</a>,
                  it should be <strong>easy</strong> to fix!</p>
          <?php if (empty($_POST['uninstall']) ) : ?>
              <form method="post" action="">
                  <input type="submit" class="submit button ld-button" name="uninstall" value="Uninstall">
              </form>
          </div>
      </div>
  </div>
</body>
</html>
<?php exit; endif; ?>
                  <ul class="ld-steps">
    <?php

try {

// Start

set_time_limit(300);

out('Uninstallation starting');

$root = dirname(__FILE__);

if (!file_exists($root . '/dist/site.php')) {
    error("It seems La Distrbution is already uninstalled.");
}

require_once $root . '/dist/site.php';

$site = Zend_Registry::get('site');

foreach ($site->getInstances() as $id => $infos) {
    if ($infos['type'] == 'application') {
        $instance = $site->getInstance($id);
        if ($instance) {
            try {
                $name = $instance->getName();
                $site->deleteInstance($instance);
                out("Application '$name' deleted");
            } catch (Exception $e) {
                error("Can't delete application '$name'. " . $e->getMessage() );
            }
        }
    }
}

Ld_Files::purgeTmpDir(0);

$directories = array('js', 'css', 'shared', 'lib', 'dist', 'repositories', 'tmp');
foreach ($directories as $id) {
    $path = $site->getDirectory($id);
    if (Ld_Files::exists($path)) {
        Ld_Files::unlink($path);
        out("Folder '$id' removed.");
    }
}

$files = array('.htaccess', 'index.php');
foreach ($files as $file) {
    if (Ld_Files::exists($file)) {
        Ld_Files::unlink($file);
        out("File '$file' removed.");
    }
}

out("Uninstallation completed");

// Catch

} catch (Exception $e) {

    error( $e->getMessage() );

}

?>

</ul>

<p>We sincerely hope everything was fine with La Distribution.</p>

</div></div></div>
</body></html>