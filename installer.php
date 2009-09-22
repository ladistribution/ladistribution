<?php

// User Configuration

// Uncomment this line if you want to turn debug infos on
// define('LD_DEBUG', true);

// Uncomment this line if your web server isn't Apache or if mod_rewrite is not available
// define('LD_REWRITE', false);

// Uncomment & modify this line if you want to localise your installation
// define('LD_LOCALE', 'fr_FR');

// Uncomment & modify this line if you want to target a specific release
// instead of the default one (edge)
// define('LD_RELEASE', 'barbes');

// Uncomment & modify this line if you want the installer talk to antoher server
// eg: 'http://localhost/ld/'
// define('LD_SERVER', 'http://ladistribution.net/');

// Default Configuration

date_default_timezone_set('UTC');

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
    }
    require($file);
}

function is_requirable($lib)
{
    $paths = explode(PATH_SEPARATOR, get_include_path());
    foreach($paths as $path) {
        if (@file_exists("$path/$lib")) {
            return true;
        }
    }
    return false;
}

// Test PHP version

if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
    die ( '- Failure. La Distribution needs PHP 5.2.x or higher to run. You are currently running PHP ' . PHP_VERSION . '.' );
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
        mkdir($directory, 0777, true);
    }
}

set_include_path( $directories['lib'] . PATH_SEPARATOR . get_include_path() );

echo '- Directories OK<br>';
flush();

// Essentials

$essentials_directories = array(
    'ld'     => $directories['lib'] . '/Ld',
    'common' => $directories['lib'] . '/clearbricks/common',
    'zip'    => $directories['lib'] . '/clearbricks/zip'
);

foreach ($essentials_directories as $directory) {
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
}

$essentials = array(
    $essentials_directories['ld'] . '/Files.php'         => LD_SERVER . 'installer/Files.txt',
    $essentials_directories['common'] . '/lib.files.php' => LD_SERVER . 'installer/lib.files.txt',
    $essentials_directories['zip'] . '/class.zip.php'    => LD_SERVER . 'installer/class.zip.txt',
    $essentials_directories['zip'] . '/class.unzip.php'  => LD_SERVER . 'installer/class.unzip.txt'
);

foreach ($essentials as $file => $source) {
    install_if_not_exists_and_require($file, $source);
}

echo '- Essentials OK<br>';
flush();

// Zend & Ld libraries
    
$base_libs = array();

if (!is_requirable('Zend/Loader.php')) {
    $base_libs['zend-framework'] = LD_SERVER . 'repositories/' . LD_RELEASE . '/main/lib/lib-zend-framework/lib-zend-framework.zip';
}

if (!is_requirable('Ld/Installer.php')) {
    $base_libs['ld-libraries'] = LD_SERVER . 'repositories/' . LD_RELEASE . '/main/lib/lib-ld/lib-ld.zip';
}

foreach ($base_libs as $name => $source) {
    $archiveName = $directories['tmp'] . '/' . $name . '.zip';
    $targetDirectory = $directories['tmp'] . '/' . $name;
    if (!file_exists($targetDirectory)) {
        $zip = file_get_contents($source);
        Ld_Files::put($archiveName, $zip);
        $uz = new fileUnzip($archiveName);
        $uz->unzipAll($targetDirectory);
    }
    Ld_Files::copy($targetDirectory . '/lib', $directories['lib']);
    if (file_exists($targetDirectory . '/shared')) {
        Ld_Files::copy($targetDirectory . '/shared', $directories['shared']);
    }
}

echo '- Zend & Ld OK<br>';
flush();

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

echo " - Init OK<br>\n";

// Handle locales

if (defined('LD_LOCALE')) {
    Ld_Files::createDirIfNotExists($site->getDirectory('shared') . '/locales');
    if (constant('LD_LOCALE') == 'fr_FR') {
        $site->addRepository(array('type' => 'remote', 'endpoint' => LD_SERVER . 'repositories/' . LD_RELEASE . '/fr', 'name' => ''));
        $site->updateLocales(array('en_US','fr_FR'));
        $site->createInstance('ld-locale-fr-fr');
    }
    echo " - Locale OK<br>\n";
}

// Clean TMP
foreach ($base_libs as $name => $source) {
    $targetDirectory = $directories['tmp'] . '/' . $name;
    Ld_Files::unlink($targetDirectory); 
}

// Instances registry

$instances = $site->getInstances();
if (empty($instances)) {
    $instances = array();
    $instances[$site->getUniqId()] = array('package' => 'lib-zend-framework', 'type' => 'lib', 'version' => '1.9.2-1');
    $instances[$site->getUniqId()] = array('package' => 'lib-ld', 'type' => 'lib', 'version' => '0.3-39-1');
    $site->updateInstances($instances);
}

echo '- Registry OK<br>';
flush();

// Install or Update Admin

foreach ($site->getInstances() as $id => $infos) {
    if ($infos['package'] == 'admin') {
        $admin = $site->getInstance($id);
        break;
    }
}

if (empty($admin)) {
    $admin = $site->createInstance('admin', array('title' => 'La Distribution Admin', 'path' => 'admin'));
    echo '- Install Admin OK<br>';
} else {
    $site->updateInstance($admin);
    echo '- Update Admin OK<br>';
}

// Base Index

$root_index = $root . '/index.php';
if (!file_exists($root_index)) {
    $index  = '<?php' . "\n";
    $index .= "define('LD_ROOT_CONTEXT', true);\n";
    $index .= "require_once('admin/dispatch.php');\n";
    Ld_Files::put($root_index, $index);
}

// Base .htaccess
if (constant('LD_REWRITE')) {
    $root_htaccess = $root . '/.htaccess';
    $path = $site->getPath() . '/';
    $htaccess  = "RewriteEngine on\n";
    $htaccess .= "RewriteBase $path\n";
    $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccess .= "RewriteRule !\.(js|ico|gif|jpg|png|css|swf|php|txt)$ index.php\n";
    Ld_Files::put($root_htaccess, $htaccess);
}

echo 'Everything OK. <a href="' . $admin->getUrl() . '">Go admin</a>.';
flush();

// Catch

} catch (Exception $e) {

echo '- FAIL: ' . $e->getMessage() . '<br>';

}
