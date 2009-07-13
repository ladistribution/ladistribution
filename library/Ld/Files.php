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
    public static function unlink($dir, $deleteRootToo = true)
    {
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
               self::unlink($dir.'/'.$obj, true);
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
    public static function copy($source, $target)
    {
        if (is_dir($source)) {
            if (!file_exists($target)) {
                mkdir( $target, 0777, true);
                if (defined('LD_UNIX_USER')) {
                    chown($target, LD_UNIX_USER);
                }
                if (defined('LD_UNIX_PERMS')) {
                    chmod($target, LD_UNIX_PERMS);
                }
            }
            if (!is_writable($target)) {
                echo "<b>Skipped</b>: $target (not writable).<br/><br/>";
                return false;
            }
            $d = dir( $source );
            while ( FALSE !== ( $entry = $d->read() ) ) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    self::copy($Entry, $target . '/' . $entry);
                    continue;
                }
                $result = copy($Entry, $target . '/' . $entry);
                if (defined('LD_UNIX_USER')) {
                    chown($target . '/' . $entry, LD_UNIX_USER);
                }
                if (defined('LD_UNIX_PERMS')) {
                    chmod($target . '/' . $entry, LD_UNIX_PERMS);
                }
            }
            $d->close();
        } else {
            copy($source, $target);
            if (defined('LD_UNIX_USER')) {
                chown($target, LD_UNIX_USER);
            }
            if (defined('LD_UNIX_PERMS')) {
                chmod($target, LD_UNIX_PERMS);
            }
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
            if (defined('LD_UNIX_USER')) {
                chown($dir, LD_UNIX_USER);
            }
        }
    }

    public static function put($file, $content)
    {
        file_put_contents($file, $content);
        if (defined('LD_UNIX_USER')) {
            chown($file, LD_UNIX_USER);
        }
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
        if (isset($content)) {
            return Zend_Json::decode($content);
        }
        return array();
    }

}
