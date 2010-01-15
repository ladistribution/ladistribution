<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Zip
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Zip
{

    public function pack($directories, $archive)
    {
        // with PHP PECL extension
        if (class_exists('Ld_Zip_Archive', false)) {
            Ld_Files::log('pack (ZipArchive)', $archive);
            $zip = new Ld_Zip_Archive();
            if ($zip->open($archive, ZIPARCHIVE::CREATE) !== TRUE) {
                throw new Exception('Could not open archive');
            }
            foreach ($directories as $name => $directory) {
                if (Ld_Files::exists($directory)) {
                    $directory = Ld_Files::real($directory);
                    Ld_Files::log('addDirectory', "$directory => $name");
                    $zip->addDirectory($directory, $name);
                }
            }
            $zip->close();
        // with Clearbricks library
        } else {
            Ld_Files::log('pack (fileZip)', $archive);
            $fp = fopen($archive, 'wb');
            $zip = new fileZip($fp);
            foreach ($directories as $name => $directory) {
                if (Ld_Files::exists($directory)) {
                    Ld_Files::log('addDirectory', "$directory => $name");
                    $zip->addDirectory($directory, $name, true);
                }
            }
            $zip->write();
        }
    }

    public static function extract($archive, $destination)
    {
        // with PHP PECL extension
        if (class_exists('Ld_Zip_Archive', false)) {
            Ld_Files::log('unzip (ZipArchive)', "$archive => $destination");
            $zip = new ZipArchive() ;
            if ($zip->open($archive) !== TRUE) {
                throw new Exception('Could not open archive');
            }
            $zip->extractTo($destination);
            $zip->close();
        // with Clearbricks library
        } else {
            Ld_Files::log('unzip (fileUnzip)', "$archive => $destination");
            $uz = new fileUnzip($archive);
            $uz->unzipAll($destination);
        }
    }

}

if (class_exists('ZipArchive'))
{

    class Ld_Zip_Archive extends ZipArchive
    {
        public function addDirectory($absolutePath, $path, $dummy = '')
        {
            $this->addEmptyDir($path);
            foreach (Ld_Files::getFiles($absolutePath) as $file) {
                $this->addFile("$absolutePath/$file", "$path/$file");
            }
            foreach (Ld_Files::getDirectories($absolutePath) as $directory) {
                $this->addDirectory("$absolutePath/$directory", "$path/$directory");
            }
        }
    }

}
