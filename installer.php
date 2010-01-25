<?php

// User Configuration

// Uncomment this line if you want to turn debug infos on
// define('LD_DEBUG', true);

// Uncomment this line if you want to force Unix permissions
// define('LD_UNIX_PERMS', 0777);

// Uncomment this line if your web server isn't Apache or if mod_rewrite is not available
// define('LD_REWRITE', false);

// Uncomment this line if you want to enable memcached support in your installation
// define('LD_MEMCACHED', true);

// Uncomment & modify this line if you want to localise your installation
// define('LD_LOCALE', 'fr_FR');

// Uncomment & modify this line if you want to target a specific release
// instead of the default one (edge)
// define('LD_RELEASE', 'concorde');

// Uncomment & modify this line if you want the installer to talk to another server
// eg: 'http://localhost/ld/'
// define('LD_SERVER', 'http://ladistribution.net/');

// Uncomment this line in case of php memory issues (32M is the minimum recommended)
// ini_set("memory_limit", "32M");

// Default Configuration

set_time_limit(300);

date_default_timezone_set('UTC');

defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.net/');

defined('LD_RELEASE') OR define('LD_RELEASE', 'edge');

defined('LD_DEBUG') OR define('LD_DEBUG', false);

defined('LD_SESSION') OR define('LD_SESSION', false);

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

function out($message)
{
    if (defined('LD_CLI') && constant('LD_CLI')) {
        fwrite(STDOUT, "- $message");
        fwrite(STDOUT, PHP_EOL);
    } else {
        echo "- $message<br/>\n";
        flush();
    }
}

// Test PHP version

if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
    out('- Failure. La Distribution needs PHP 5.2.x or higher to run. You are currently running PHP ' . PHP_VERSION . '.' );
    exit;
}

// Try

try {

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
            $msg = "- Failure. Can't create folder $directory. Check your permissions.";
            die($msg);
        }
        mkdir($directory);
        fix_perms($directory);
    }
}

set_include_path( $directories['lib'] . PATH_SEPARATOR . get_include_path() );

out('Directories OK');

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

out('Essentials OK');

// Zend & Ld libraries

$base_libs = array();

if (!is_requirable('Zend/Loader/Autoloader.php')) {
    $base_libs['zend-framework'] = LD_SERVER . 'repositories/' . LD_RELEASE . '/main/lib/lib-zend-framework/lib-zend-framework.zip';
}

if (!is_requirable('Ld/Installer.php')) {
    $base_libs['ld-libraries'] = LD_SERVER . 'repositories/' . LD_RELEASE . '/main/lib/lib-ld/lib-ld.zip';
}

foreach ($base_libs as $name => $source) {
    $archiveName = $directories['tmp'] . '/' . $name . '.zip';
    $targetDirectory = $directories['tmp'] . '/' . $name . '-' . LD_RELEASE;
    if (!file_exists($targetDirectory)) {
        Ld_Http::download($source, $archiveName);
        Ld_Zip::extract($archiveName, $targetDirectory);
    }
    Ld_Files::copy($targetDirectory . '/lib', $directories['lib']);
    if (file_exists($targetDirectory . '/shared')) {
        Ld_Files::copy($targetDirectory . '/shared', $directories['shared']);
    }
}

out('Zend & Ld OK');

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

out('Init OK');

// Upgrade repositories (if needed)

$endpoints = array();
$repositories = $site->getRepositoriesConfiguration();
foreach ($repositories as $id => $repository) {
    if (isset($repository['endpoint'])) {
        if (strpos($repository['endpoint'], LD_SERVER . 'repositories/barbes') !== false) {
            $repositories[$id]['endpoint'] = str_replace(
                LD_SERVER . 'repositories/barbes', LD_SERVER . 'repositories/' . LD_RELEASE, $repository['endpoint']);
            $repository_upgrade = true;
        }
        $endpoints[] = $repositories[$id]['endpoint'];
    }
}
if (isset($repository_upgrade)) {
    $site->saveRepositoriesConfiguration($repositories);
    out('Repositories OK');
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
    // - should be replaced by updateLocales after Barbes
    Ld_Files::putJson($site->getDirectory('dist') . '/locales.json', array('en_US', LD_LOCALE));
    // Install main package
    // - should be simplified after Barbes
    $packageId = "ld-locale-" . str_replace('_', '-', strtolower(LD_LOCALE));
    $packages = $site->getPackages();
    if (isset($packages[$packageId])) {
        if (!method_exists($site, 'isPackageInstalled') || !$site->isPackageInstalled($packageId)) {
            $site->createInstance($packageId);
        }
    }
    out('Locale OK');
}

// Clean TMP
foreach ($base_libs as $name => $source) {
    $targetDirectory = $directories['tmp'] . '/' . $name . '-' . LD_RELEASE;
    Ld_Files::unlink($targetDirectory);
}

// Instances registry

$instances = $site->getInstances();
if (empty($instances)) {
    $instances = array();
    $instances[$site->getUniqId()] = array('package' => 'lib-zend-framework', 'type' => 'lib', 'version' => '1.9.7-1');
    $instances[$site->getUniqId()] = array('package' => 'lib-ld', 'type' => 'lib', 'version' => '0.3-57-2');
    $site->updateInstances($instances);
}

out('Registry OK');


// Install or Update Admin

foreach ($site->getInstances() as $id => $infos) {
    if ($infos['package'] == 'admin') {
        $admin = $site->getInstance($id);
        break;
    }
}

if (empty($admin)) {
    $admin = $site->createInstance('admin', array('title' => 'La Distribution Admin', 'path' => 'admin'));
    out('Install Admin OK');
} else {
    $site->updateInstance($admin);
    // Localise admin
    if (defined('LD_LOCALE') && constant('LD_LOCALE') == 'fr_FR' && constant('LD_RELEASE') != 'barbes') {
        $packageId = "admin-locale-" . str_replace('_', '-', strtolower(LD_LOCALE));
        // should be replaced by hasExtension after Barbes
        try {
            $admin->getExtension($packageId);
        } catch (Exception $e) {
            $admin->addExtension($packageId);
        }
    }
    out('Update Admin OK');
}

out('Everything OK. <a href="' . $admin->getUrl() . '">Go to admin</a>.');

// Catch

} catch (Exception $e) {

    out( 'FAIL: ' . $e->getMessage() );

}
