<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Storage;

/**
 * HybridAuth storage manager interface
 */
interface StorageInterface
{
    /**
    * Retrieve a item from storage
    *
    * @param string $key
    *
    * @return mixed
    */
    public function get($key);

    /**
    * Add or Update an item to storage
    *
    * @param string $key
    * @param string $value
    */
    public function set($key, $value);

    /**
    * Delete an item from storage
    *
    * @param string $key
    */
    public function delete($key);

    /**
    * Delete a item from storage
    *
    * @param string $key
    */
    public function deleteMatch($key);

    /**
    * Clear all items in storage
    */
    public function clear();
}
