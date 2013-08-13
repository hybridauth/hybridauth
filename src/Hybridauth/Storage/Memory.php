<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Storage;

use Hybridauth\Exception;
use Hybridauth\Storage\StorageInterface;

/**
* HybridAuth storage manager
* Use this to keep credentials in memory for this request(They must be loaded on each request)
*/
class Memory implements StorageInterface
{
    private $store_var = array();
    private $config_var = array();

    // --------------------------------------------------------------------

    function config( $key, $value = null )
    {
        $key = strtolower ( $key );

        if ( $value ){
            $this->config_var[$key] = serialize($value);
        }
        elseif( isset( $this->config_var[$key] ) ){
            return unserialize( $this->config_var[$key] );
        }

        return null;
    }

    // --------------------------------------------------------------------

    function get( $key )
    {
        $key = strtolower($key);
        return isset($this->store_var[$key]) ? unserialize($this->store_var[$key]) : null;
    }

    // --------------------------------------------------------------------

    function set( $key, $value )
    {
        $key = strtolower($key);
        $this->store_var[$key] = serialize($value);
    }

    // --------------------------------------------------------------------

    function delete( $key )
    {
        $this->deleteMatch($key . '$');
    }

    // --------------------------------------------------------------------

    function deleteMatch( $key )
    {
        $key = '/^' . strtolower($key) . '/';
        foreach(array_keys($this->store_var) as $store_key) {
            if ( preg_match( $key, $store_key ) ) {
                unset( $this->store_var[$k] );
            }
        }
    }

    // --------------------------------------------------------------------

    function dump() {
        return $this->dumpMatch('.*');
    }

    // --------------------------------------------------------------------

    function dumpMatch( $key ) {
        $key = '/^' . strtolower($key) . '/';
        $return = array();
        foreach(array_keys($this->store_var) as $store_key) {
            if ( preg_match( $key, $store_key ) ) {
                $return[$store_key] = $this->store_var[$store_key];
            }
        }
        return serialize($return);
    }

    // --------------------------------------------------------------------

    function load( $data ) {
        $unserialized = unserialize($data);
        $falseCheck = 'b:0;'; //serialize(false);
        if ( is_array($unserialized) ) {
            foreach ( $unserialized as $k => $v ) {
                $this->set($k, ($v == $falseCheck || unserialize($v) !== false) ? unserialize($v) : $v);
            }
        }
    }
}
