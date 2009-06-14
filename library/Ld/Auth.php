<?php

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
