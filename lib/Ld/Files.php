<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Files
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
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
        if ($initial) {
            self::log('unlink', $dir);
        }
        if (is_file($dir)) {
            unlink($dir);
            return;
        }
        if (!file_exists($dir) || !$dh = opendir($dir)) {
            return;
        }
        while (false !== ($obj = readdir($dh))) {
            if ($obj == '.' || $obj == '..') {
                continue;
            }
            if (is_dir($dir . '/' . $obj)) {
               self::unlink($dir.'/'.$obj, true, false);
            } else {
               unlink($dir . '/' . $obj);
            }
        }
        closedir($dh);
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
        if (is_dir($source)) {
            if (!file_exists($target) && is_writable(dirname($target))) {
                mkdir( $target, 0777, true);
                self::updatePermissions($target);
            }
            if (!is_writable($target)) {
                if (file_exists($target)) {
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
            $d = dir( $source );
            while ( FALSE !== ( $entry = $d->read() ) ) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $origin = $source . '/' . $entry;
                $destination = $target . '/' . $entry;
                if (is_dir($origin)) {
                    self::copy($origin, $destination, false);
                    continue;
                }
                if (file_exists($destination) && !is_writable($destination)) {
                    self::log('skipped', "$destination (not writable)");
                    continue;
                }
                $result = copy($origin, $destination);
                self::updatePermissions($destination);
            }
            $d->close();
        } else {
            copy($source, $target);
            self::updatePermissions($target);
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
        $exclude = array_merge(array('.', '..'), (array)$exclude);

        $files = array();
        $directories = array();

        if (file_exists($dir) && $dh = opendir($dir)) {
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

    // http://fr2.php.net/manual/en/function.realpath.php
    public static function realpath($path)
    {
        $out = array();
        foreach (explode('/', $path) as $i => $fold) {
            if ($fold == '' || $fold == '.') {
                continue;
            }
            if ($fold == '..' && $i > 0 && end($out) != '..') {
                array_pop($out);
            } else {
                $out[]= $fold;
            }
        }
        return ($path{0}=='/'?'/':'').join('/', $out);
    }
    
    public static function cleanpath($path)
    {
        $path = trim($path, " /\t\n\r");
        $path = self::realpath( $path );
        return $path;
    }
    
    public static function is_requirable($file)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            if (@file_exists("$path/$file")) {
                return true;
            }
        }
        return false;
    }

    public static function createDirIfNotExists($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            self::updatePermissions($dir);
        }
    }

    public static function put($file, $content)
    {
        if (file_exists($file) && !is_writable($file)) {
            throw new Exception("Can't write $file.");
        }
        file_put_contents($file, $content);
        self::updatePermissions($file);
    }

    public static function putJson($file, $content)
    {
        self::put($file, Zend_Json::encode($content));  
    }

    public static function get($file)
    {
        if (file_exists($file)) {
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
        $ignore = array_merge(array('dist', '.svn'), $ignore);
        foreach (self::getDirectories($root, $ignore) as $directory) {
            $dist = $root . '/' . $directory . '/dist';
            if (file_exists($dist)) {
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
                Ld_Files::unlink(LD_TMP_DIR . '/' . $file);
            }
        }
        foreach (self::getDirectories(LD_TMP_DIR, array('cache')) as $dir) {
            $filemtime = filemtime(LD_TMP_DIR . '/' . $dir);
            $age = time() - $filemtime;
            if ($age > $maxAge) {
                Ld_Files::unlink(LD_TMP_DIR . '/' . $dir);
            }
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

}