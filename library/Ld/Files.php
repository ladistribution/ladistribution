<?php

class Ld_Files
{

    public static function includes($dir)
    {
        $result = self::scanDir($dir);
        foreach ($result['files'] as $file) {
            include $dir . '/' . $file;
        }
    }

    // from http://fr.php.net/unlink
    public static function unlink($dir, $deleteRootToo = true)
    {
        if (is_file($dir)) {
            unlink($dir);
            return;
        }
        if(!file_exists($dir) || !$dh = opendir($dir)) {
            return;
        }
        while (false !== ($obj = readdir($dh))) {
            if($obj == '.' || $obj == '..') {
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
            // free.fr
            if (is_dir($dir)) {
                $result = rmdir($dir);
                if (!$result) unlink($dir);
            }
            // @rename($dir, "deleted"); 
        }
        return;
    }

    public static function copy( $source, $target )
    {
        // echo "copy:$source:$target\n";
        if ( is_dir( $source ) ) {
            if (!file_exists($target)) {
                mkdir( $target, 0777, true);
                if (defined('LD_UNIX_USER')) {
                    chown($target, LD_UNIX_USER);
                }
            }
            $d = dir( $source );
            while ( FALSE !== ( $entry = $d->read() ) ) {
                if ( $entry == '.' || $entry == '..' ) {
                    continue;
                }
                $Entry = $source . '/' . $entry;
                if ( is_dir( $Entry ) ) {
                    self::copy( $Entry, $target . '/' . $entry );
                    continue;
                }
                copy( $Entry, $target . '/' . $entry );
                if (defined('LD_UNIX_USER')) {
                    chown($target . '/' . $entry, LD_UNIX_USER);
                }
            }
            $d->close();
        } else {
            copy( $source, $target );
            if (defined('LD_UNIX_USER')) {
                chown($target, LD_UNIX_USER);
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
        $out=array();
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
        foreach($paths as $path) {
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
        }
    }

    public static function put($file, $content)
    {
        file_put_contents($file, $content);
    }

    public static function get($file)
    {
        return file_get_contents($file);
    }

}