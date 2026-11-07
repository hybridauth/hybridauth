<?php

namespace Hybridauth\Data;

use Hybridauth\Exception\RuntimeException;

/** Security tokens must remain unpredictable even when the application seeds mt_rand(). */
class Random
{
    public static function token()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(32));
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes(32, $strong);
            if ($strong && $bytes !== false && strlen($bytes) === 32) {
                return bin2hex($bytes);
            }
        }

        throw new RuntimeException('A cryptographically secure random source is required.');
    }

    public static function equals($expected, $actual)
    {
        if (!is_string($expected) || !is_string($actual) || $expected === '' || strlen($expected) !== strlen($actual)) {
            return false;
        }
        if (function_exists('hash_equals')) {
            return hash_equals($expected, $actual);
        }
        $difference = 0;
        for ($i = 0, $length = strlen($expected); $i < $length; $i++) {
            $difference |= ord($expected[$i]) ^ ord($actual[$i]);
        }
        return $difference === 0;
    }
}
