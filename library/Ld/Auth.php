<?php

class Ld_Auth
{

    public static function restricted()
    {
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $filename = LD_DIST_DIR . '/users.php';
            if (file_exists($filename)) {
                return true;
            }
            $content = trim( file_get_contents($filename) );
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $user = explode(":", trim($line));
                if ($_SERVER['PHP_AUTH_USER'] == $user[0] && sha1($_SERVER['PHP_AUTH_PW']) == $user[1]) {
                    return true;
                }
            }
        }
        self::unauthorized();
    }

    public static function unauthorized()
    {
        header("HTTP/1.0 401 Unauthorized");
        header('WWW-Authenticate: Basic realm="ld"');
        die('Unauthorized.');
    }

}