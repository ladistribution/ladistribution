<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Auth
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Auth
{

    public static function generatePhrase($length = 64)
    {
        $chars = "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $i = 0;
        $phrase = "";
        while ($i <= $length) {
            $phrase .= $chars{mt_rand(0,strlen($chars)-1)};
            $i++;
        }
        return $phrase;
    }

}
