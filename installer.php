<?php

// User Configuration

// Uncomment this line if you want to turn debug infos on
// define('LD_DEBUG', true);

// Uncomment this line if you want to force Unix permissions
// define('LD_UNIX_PERMS', 0777);

// Uncomment this line if your web server isn't Apache or if mod_rewrite is not available
// define('LD_REWRITE', false);

// Uncomment & modify this line if you want to localise your installation
// define('LD_LOCALE', 'fr_FR');

// Uncomment & modify this line if you want to target a specific release
// instead of the default one (edge)
// define('LD_RELEASE', 'danube');

// Uncomment & modify this line if you want the installer to talk to another server
// eg: 'http://localhost/ld/'
// define('LD_SERVER', 'http://ladistribution.net/');

// Uncomment this line in case of php memory issues (32M is the minimum recommended)
// ini_set("memory_limit", "32M");

// Default Configuration

defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.net/');

defined('LD_RELEASE') OR define('LD_RELEASE', 'edge');

defined('LD_DEBUG') OR define('LD_DEBUG', false);

// Functions

function install_if_not_exists_and_require($file, $source)
{
    if (!file_exists($file)) {
        $context = stream_context_create(array(
            'http' => array(
                'method'  => 'GET',
                'header'  => "User-Agent: La Distribution Installer\r\n"
            )
        ));
        $content = file_get_contents($source, false, $context);
        if (empty($content)) {
            $msg = "- Failure. Can't retrieve file $source.";
            die($msg);
        }
        file_put_contents($file, $content);
        fix_perms($file);
    }
    require($file);
}

function fix_perms($target)
{
    if (defined('LD_UNIX_USER')) {
        chown($target, LD_UNIX_USER);
    }
    if (defined('LD_UNIX_PERMS')) {
        chmod($target, LD_UNIX_PERMS);
    }
}

function is_requirable($lib)
{
    $paths = explode(PATH_SEPARATOR, get_include_path());
    foreach ($paths as $path) {
        if (@file_exists("$path/$lib")) {
            return true;
        }
    }
    return false;
}

function out($message, $class = 'ok')
{
    if (defined('LD_CLI') && constant('LD_CLI')) {
        fwrite(STDOUT, "- $message");
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
        <input type="submit" class="submit button ld-button" name="install" value="Try Again">
    </form>
    </div></div></div>
    </body></html>
    <?php
    exit;
}

// Starts Output

if (!defined('LD_CLI') || !constant('LD_CLI')) {
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>La Distribution Installer</title>
    <meta charset="utf-8">
    <link href="<?php echo LD_SERVER ?>css/h6e-minimal/h6e-minimal.css?v=0.1-3" rel="stylesheet" type="text/css">
    <link href="<?php echo LD_SERVER ?>css/ld-ui/ld-ui.css?v=0.4-25" rel="stylesheet" type="text/css">
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
          <h1 class="h6e-page-title">La Distribution Installer</h1>
          <div class="h6e-simple-footer" >
              Powered by <a href="http://ladistribution.net/">La Distribution</a>,
              a community project initiated by <a href="http://h6e.net/">h6e</a>. 
          </div>
          <div class="h6e-post-content">
              <p>Thank you for downloading this installer.</p>
              <p>It will help you install La Distribution in <strong>less than a minute</strong>.</p>
              <p>If you encouter any problem, please visit <a href="http://ladistribution.net/en/forums/">our forums</a>,
                  it should be <strong>easy</strong> to fix!</p>
          <?php if (empty($_POST['install'])) : ?>
              <form method="post" action="">
                  <input type="submit" class="submit button ld-button" name="install" value="Start">
              </form>
          </div>
      </div>
  </div>
</body>
</html>
<?php exit; endif; ?>
                  <ul class="ld-steps">
    <?php
}

// Test PHP version

if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
    error('La Distribution needs PHP 5.2.x or higher to run. You are currently running PHP ' . PHP_VERSION . '.');
}

// Try

try {

// Start

set_time_limit(300);

date_default_timezone_set('UTC');

out('Installation starting');

// Directories

$root = dirname(__FILE__);

$directories = array(
    'lib'    => $root . '/lib',
    'dist'   => $root . '/dist',
    'shared' => $root . '/shared',
    'tmp'    => $root . '/tmp',
);

foreach ($directories as $name => $directory) {
    if (!file_exists($directory)) {
        if (!is_writable(dirname($directory))) {
            error("Can't create folder $directory. Check your permissions.");
        }
        mkdir($directory);
        fix_perms($directory);
    }
}

set_include_path( $directories['lib'] . PATH_SEPARATOR . get_include_path() );

out('Directories created');

// Essentials

$essentials_directories = array(
    'ld'     => $directories['lib'] . '/Ld',
    'common' => $directories['lib'] . '/clearbricks/common',
    'zip'    => $directories['lib'] . '/clearbricks/zip'
);

foreach ($essentials_directories as $directory) {
    $parent = dirname($directory);
    if (!file_exists($parent)) {
        mkdir($parent);
        fix_perms($parent);
    }
    if (!file_exists($directory)) {
        mkdir($directory);
        fix_perms($directory);
    }
}

$essentials = array(
    $essentials_directories['ld'] . '/Files.php'         => LD_SERVER . 'installer/Files.txt',
    $essentials_directories['ld'] . '/Zip.php'           => LD_SERVER . 'installer/Zip.txt',
    $essentials_directories['ld'] . '/Http.php'          => LD_SERVER . 'installer/Http.txt',
    $essentials_directories['common'] . '/lib.files.php' => LD_SERVER . 'installer/lib.files.txt',
    $essentials_directories['zip'] . '/class.zip.php'    => LD_SERVER . 'installer/class.zip.txt',
    $essentials_directories['zip'] . '/class.unzip.php'  => LD_SERVER . 'installer/class.unzip.txt'
);

foreach ($essentials as $file => $source) {
    install_if_not_exists_and_require($file, $source);
}

out('Essentials libraries loaded');

// Zend & Ld libraries

$base_libs = array();

if (!is_requirable('Zend/Loader/Autoloader.php')) {
    $base_libs['Zend'] = LD_SERVER . 'repositories/' . LD_RELEASE . '/main/lib/lib-zend-framework/lib-zend-framework.zip';
}

if (!is_requirable('Ld/Installer.php')) {
    $base_libs['Ld'] = LD_SERVER . 'repositories/' . LD_RELEASE . '/main/lib/lib-ld/lib-ld.zip';
}

foreach ($base_libs as $name => $source) {
    $archiveName = $directories['tmp'] . '/' . $name . '.zip';
    $targetDirectory = $directories['tmp'] . '/' . $name . '-' . LD_RELEASE;
    if (!Ld_Files::exists($targetDirectory)) {
        Ld_Http::download($source, $archiveName);
        Ld_Zip::extract($archiveName, $targetDirectory);
    }
    Ld_Files::copy($targetDirectory . '/lib', $directories['lib'] . (LD_RELEASE == 'concorde' ? '' : '/' . $name));
    if ($name == 'Ld') {
        Ld_Files::copy($targetDirectory . '/locales', $directories['shared'] . '/locales/ld');
    }
}

out('Zend Framework and Ld Libraries loaded');

if (defined('LD_CLI_INSTALL') && constant('LD_CLI_INSTALL')) {
   out('CLI install OK');
   return;
}

// Load Site

$loader = $directories['lib'] . '/Ld/Loader.php';
if (file_exists($loader)) { require_once $loader; } else { require_once 'Ld/Loader.php'; }
$site = Ld_Loader::loadSite(dirname(__FILE__));

// Detect base path
if (!empty($_SERVER["SCRIPT_NAME"])) {
    $site->path = str_replace('/installer.php', '', $_SERVER["SCRIPT_NAME"]);
}

// Init

$site->init();

out('Site initialised');

// Upgrade repositories (if needed)

$endpoints = array();
$repositories = $site->getRepositoriesConfiguration();
foreach ($repositories as $id => $repository) {
    if (isset($repository['endpoint'])) {
        // upgrade to test/local repositories
        $default_server = 'http://ladistribution.net/';
        if (LD_SERVER != $default_server) {
            if (strpos($repository['endpoint'], $default_server) !== false) {
                $repository['endpoint'] = str_replace($default_server, LD_SERVER, $repository['endpoint']);
                $repository_upgrade = true;
            }
        }
        // upgrade old releases repositories
        $old_releases = LD_RELEASE == 'concorde' ? array('barbes') : array('barbes', 'concorde');
        foreach ($old_releases as $release) {
            if (strpos($repository['endpoint'], LD_SERVER . 'repositories/' . $release) !== false) {
                $repositories[$id]['endpoint'] = str_replace(
                    LD_SERVER . 'repositories/' . $release,
                    LD_SERVER . 'repositories/' . LD_RELEASE,
                    $repository['endpoint']
                );
                $repository_upgrade = true;
            }
        }
        $endpoints[] = $repositories[$id]['endpoint'];
    }
}
if (isset($repository_upgrade)) {
    $site->saveRepositoriesConfiguration($repositories);
    out('Repositories upgraded');
}

// Instances registry

$instances = $site->getInstances();
if (empty($instances)) {
    $instances = array();
    foreach ($base_libs as $name => $source) {
        $targetDirectory = $directories['tmp'] . '/' . $name . '-' . LD_RELEASE;
        $manifest = Ld_Manifest::loadFromDirectory($targetDirectory);
        $infos = $manifest->getInfos();
        $instances[$site->getUniqId()] = array('package' => $manifest->getId(), 'type' => 'lib', 'version' => $infos['version']);
        Ld_Files::unlink($targetDirectory);
    }
    $site->updateInstances($instances);
    out('Registry updated');
}

// Handle locales

if (defined('LD_LOCALE') && constant('LD_LOCALE') == 'fr_FR' && constant('LD_RELEASE') != 'barbes') {
    Ld_Files::createDirIfNotExists($site->getDirectory('shared') . '/locales');
    $dir = $site->getDirectory('shared') . '/locales/ld/' . LD_LOCALE;
    $repository = LD_SERVER . 'repositories/' . LD_RELEASE . '/' . substr(LD_LOCALE, 0, 2);
    if (!in_array($repository, $endpoints)) {
        $site->addRepository(array('type' => 'remote', 'endpoint' => $repository, 'name' => 'Fr Locales'));
    }
    // Set locales
    $site->updateLocales(array('en_US', LD_LOCALE));
    // Install main package
    $packageId = "ld-locale-" . str_replace('_', '-', strtolower(LD_LOCALE));
    $packages = $site->getPackages();
    if (isset($packages[$packageId]) && !$site->isPackageInstalled($packageId)) {
        $site->createInstance($packageId);
        out('Locale <em>' . LD_LOCALE . '</em> installed');
    }
}


// Install or Update Admin

foreach ($site->getInstances() as $id => $infos) {
    if ($infos['package'] == 'admin') {
        $admin = $site->getInstance($id);
        break;
    }
}

if (empty($admin)) {
    $admin = $site->createInstance('admin', array('title' => 'Administration', 'path' => 'admin'));
    out('Administration installed');
} else {
    $site->updateInstance($admin);
    // Localise admin
    if (defined('LD_LOCALE') && constant('LD_LOCALE') == 'fr_FR' && constant('LD_RELEASE') != 'barbes') {
        $packageId = "admin-locale-" . str_replace('_', '-', strtolower(LD_LOCALE));
        $admin->hasExtension($packageId) ? $admin->updateExtension($packageId) : $admin->addExtension($packageId);
    }
    out('Administration up to date');
}

out('Installation finished. Please, continue to <a href="' . $admin->getUrl() . '">the administration panel</a>.');

// Catch

} catch (Exception $e) {

    error( $e->getMessage() );

}

?>

</div></div></div>
</body></html>