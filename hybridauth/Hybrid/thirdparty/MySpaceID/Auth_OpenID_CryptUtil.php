<?php
// https://raw.github.com/myspace/myspace-php-sdk/master/source/Auth/OpenID/CryptUtil.php
// modified 

/**
 * CryptUtil: A suite of wrapper utility functions for the OpenID
 * library. 
 */

if (!defined('Auth_OpenID_RAND_SOURCE')) { 
    define('Auth_OpenID_RAND_SOURCE', null);
}

class Auth_OpenID_CryptUtil { 
    public static function getBytes($num_bytes)
    {
        static $f = null;
        $bytes = '';
        if ($f === null) {
            if (Auth_OpenID_RAND_SOURCE === null) {
                $f = false;
            } else {
                $f = fopen(Auth_OpenID_RAND_SOURCE, "r"); //@suppress errors
                if ($f === false) {
                    $msg = 'Define Auth_OpenID_RAND_SOURCE as null to ' .
                        ' continue with an insecure random number generator.';
                    trigger_error($msg, E_USER_ERROR);
                }
            }
        }
        if ($f === false) {
            // pseudorandom used
            $bytes = '';
            for ($i = 0; $i < $num_bytes; $i += 4) {
                $bytes .= pack('L', mt_rand());
            }
            $bytes = substr($bytes, 0, $num_bytes);
        } else {
            $bytes = fread($f, $num_bytes);
        }
        return $bytes;
    }
 
    public static function randomString($length, $population = null)
    {
        if ($population === null) {
            return Auth_OpenID_CryptUtil::getBytes($length);
        }

        $popsize = strlen($population);

        if ($popsize > 256) {
            $msg = 'More than 256 characters supplied to ' . __FUNCTION__;
            trigger_error($msg, E_USER_ERROR);
        }

        $duplicate = 256 % $popsize;

        $str = "";
        for ($i = 0; $i < $length; $i++) {
            do {
                $n = ord(Auth_OpenID_CryptUtil::getBytes(1));
            } while ($n < $duplicate);

            $n %= $popsize;
            $str .= $population[$n];
        }

        return $str;
    }
}
 