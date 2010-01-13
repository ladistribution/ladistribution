<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Http
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Http
{

    public static function download($url, $filename)
    {
        $local = fopen($filename, "w+");
        $remote = fopen($url, "r");
        while ( ($buffer = fread($remote, 8192)) != '' ) {
            fwrite($local, $buffer);
        }
        fclose($local);
        fclose($remote);
    }

}
