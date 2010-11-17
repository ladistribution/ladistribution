<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Files
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Files
{

    /**
     * Include all files in a given directory
     *
     * @param string $dir Directory name
     * @return null
     */
    public static function includes($dir)
    {
        $result = self::scanDir($dir);
        foreach ($result['files'] as $file) {
            include $dir . '/' . $file;
        }
    }

    /**
     * Recursively delete a directory
     *
     * Original: http://fr.php.net/manual/en/function.unlink.php#87045
     *
     * @param string $dir Directory name
     * @param boolean $deleteRootToo Delete the directory itself or not
     * @return null
     */
    public static function unlink($dir, $deleteRootToo = true, $initial = true)
    {
        $dir = self::real($dir);

        if ($initial) {
            self::log('unlink', $dir);
        }

        if (self::exists($dir . '/.preserve')) {
            self::log('skipped', "$dir (preserve detected in target)");
            return false;
        }

        if (!is_writable($dir)) {
            self::log('skipped', "$dir (not writable)");
            return false;
        }

        if (is_file($dir)) {
            unlink($dir);
            return;
        }

        foreach (self::getDirectories($dir) as $directory) {
            self::unlink($dir . '/' . $directory, true, false);
        }

        foreach (self::getFiles($dir) as $file) {
            unlink($dir . '/' . $file);
        }

        if ($deleteRootToo) {
            if (is_dir($dir)) {
                $result = rmdir($dir);
                if (!$result) unlink($dir);
            }
        }
    }

    /**
     * Copy files recursively from $source to $target
     *
     * @param string $source Directory name
     * @param string $target Directory name
     * @return null
     */
    public static function copy($source, $target, $initial = true)
    {
        if ($initial) {
            self::log('copy', "$source -> $target");
        }

        if (!self::exists($source)) {
            self::log('skipped', "$source (not existing)");
            return false;
        }

        if (self::exists($target . '/.preserve')) {
            self::log('skipped', "$target (preserve detected in target)");
            return false;
        }

        $dir = dirname($target);
        self::createDirIfNotExists($dir);

        if (is_dir($source)) {

            $target = self::real($target);
            self::createDirIfNotExists($target);

            if (!is_writable($target)) {
                if (self::exists($target)) {
                    self::log('skipped', "$target (not writable)");
                } else {
                    if (constant('LD_DEBUG')) {
                        self::log('warning', "Can't write $target.");
                    } else {
                        throw new Exception("Can't write $target.");
                    }
                }
                return false;
            }

            foreach (self::getDirectories($source) as $directory) {
                self::copy($source . '/' . $directory, $target . '/' . $directory, false);
            }

            foreach (self::getFiles($source) as $file) {
                $destination = $target . '/' . $file;
                if (self::exists($destination) && !is_writable($destination)) {
                    self::log('skipped', "$destination (not writable)");
                    continue;
                }
                $result = copy($source . '/' . $file, $destination);
                self::updatePermissions($destination);
            }

        } else {
            copy($source, $target);
            self::updatePermissions($target);
        }
    }

    public static function move($old, $new)
    {
        self::log('move', "$old -> $new");
        self::createDirIfNotExists($new);
        $result = rename($old, $new);
        if (!$result) {
            self::copy($old, $new);
            self::unlink($old);
        }
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
    }

    public static function getDirectories($dir, $exclude = array())
    {
        $result = self::scanDir($dir, $exclude);
        return $result['directories'];
    }

    public static function getFiles($dir, $exclude = array())
    {
        $result = self::scanDir($dir, $exclude);
        return $result['files'];
    }

    public static function scanDir($dir, $exclude = array())
    {
        $exclude = array_merge(array('.', '..', '.svn', '.preserve', '.DS_Store'), (array)$exclude);

        $files = array();
        $directories = array();

        if (self::exists($dir) && $dh = opendir($dir)) {
            while (false !== ($obj = readdir($dh))) {
                if (in_array($obj, $exclude)) {
                    continue;
                }
                if (is_file($dir . '/' . $obj)) {
                    $files[] = $obj;
                } else if (is_dir($dir . '/' . $obj)) {
                    $directories[] = $obj;
                }
            }
            closedir($dh);
        }

        return compact('files', 'directories');
    }

    public static function cleanpath($path)
    {
        $path = trim($path, " /\t\n\r");
        $path = self::real($path);
        return $path;
    }

    public static function is_requirable($file)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            if (@self::exists("$path/$file")) {
                return true;
            }
        }
        return false;
    }

    public static function createDirIfNotExists($dir)
    {
        if (!self::exists($dir)) {
            // doesn't works for recursive mkdir creations ...
            // if (!is_writable(dirname($dir))) {
            //     throw new Exception("Can't create $dir.");
            // }
            mkdir($dir, 0777, true);
            self::updatePermissions($dir);
        }
    }

    public static function put($file, $content)
    {
        // self::log('put', $file);
        if (self::exists($file) && !is_writable($file)) {
            throw new Exception("Can't write $file.");
        }
        file_put_contents($file, $content);
        self::updatePermissions($file);
    }

    public static function putJson($file, $content)
    {
        self::put($file, Zend_Json::encode($content));
    }

    public static function get($file, $skipTest = false)
    {
        // self::log('get', $file);
        if ($skipTest || self::exists($file)) {
            return file_get_contents($file);
        }
        return null;
    }

    public static function getJson($file)
    {
        $content = self::get($file);
        if (!empty($content)) {
            return Zend_Json::decode($content);
        }
        return array();
    }

    public static function updatePermissions($target)
    {
        if (defined('LD_UNIX_USER')) {
            chown($target, LD_UNIX_USER);
        }
        if (defined('LD_UNIX_PERMS')) {
            chmod($target, LD_UNIX_PERMS);
        }
    }

    public static function scanInstances($root, $ignore = array())
    {
        $instances = array();
        $ignore = array_merge(array('dist'), $ignore);
        foreach (self::getDirectories($root, $ignore) as $directory) {
            $dist = $root . '/' . $directory . '/dist';
            if (self::exists($dist)) {
                $instance = self::getJson($dist . '/instance.json');
                if (!empty($instance)) {
                    $instances[] = $instance;
                }
            } else {
                $otherInstances = self::scanInstances($root . '/' . $directory);
                $instances = array_merge($instances, $otherInstances);
            }
        }
        return $instances;
    }

    public static function purgeTmpDir($maxAge = 43200)
    {
        foreach (self::getFiles(LD_TMP_DIR, array('.htaccess')) as $file) {
            $filemtime = filemtime(LD_TMP_DIR . '/' . $file);
            $age = time() - $filemtime;
            if ($age > $maxAge) {
                self::unlink(LD_TMP_DIR . '/' . $file);
            }
        }
        foreach (self::getDirectories(LD_TMP_DIR, array('cache')) as $dir) {
            $filemtime = filemtime(LD_TMP_DIR . '/' . $dir);
            $age = time() - $filemtime;
            if ($age > $maxAge) {
                self::unlink(LD_TMP_DIR . '/' . $dir);
            }
        }
    }

    public static function exists($filename)
    {
        // self::log('exists', "$filename");
        return file_exists($filename);
    }

    public static function real($path)
    {
        if (self::exists($path)) {
            return realpath($path);
        }
        return $path;
    }

    public static function denyAccess($directory)
    {
        $htaccess = $directory . '/.htaccess';
        if (!Ld_Files::exists($htaccess)) {
            Ld_Files::put($htaccess, "Deny from all");
        }
        $index = $directory . '/index.php';
        if (!Ld_Files::exists($index)) {
            Ld_Files::put($index, "<?php // Silence is golden.");
        }
    }

    public static function log($action, $message)
    {
        if (defined('LD_DEBUG') && constant('LD_DEBUG')) {
            if (defined('LD_CLI') && constant('LD_CLI')) {
                fwrite(STDOUT, "# $action: $message");
                fwrite(STDOUT, PHP_EOL);
            } else {
                echo "<b>$action</b>: $message<br/>\n";
            }
        }
    }

    public static function upload() { return Ld_Http::upload(); }
    public static function download($url, $filename) { return Ld_Http::download($url, $filename); }
    public static function unzip($archive, $destination) { return Ld_Zip::extract($archive, $destination); }

}
