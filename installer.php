<?php

// Configuration

date_default_timezone_set('UTC');

define('LD_SESSION', false);

defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.net/');

defined('LD_RELEASE') OR define('LD_RELEASE', 'barbes');

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

// Clean TMP
foreach ($base_libs as $name => $source) {
    $targetDirectory = $directories['tmp'] . '/' . $name;
    Ld_Files::unlink($targetDirectory); 
}

// Instances registry

$instances = $site->getInstances();
if (empty($instances)) {
    $instances = array();
    $instances[$site->getUniqId()] = array('package' => 'lib-zend-framework', 'type' => 'lib', 'version' => '1.8.2-1');
    $instances[$site->getUniqId()] = array('package' => 'lib-ld', 'type' => 'lib', 'version' => '0.2-29-1');
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

echo 'Everything OK. <a href="' . $admin->getUrl() . '">Go admin</a>.';
flush();

// Catch

} catch (Exception $e) {

echo '- FAIL: ' . $e->getMessage() . '<br>';

}
