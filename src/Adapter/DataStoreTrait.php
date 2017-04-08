<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

trait DataStoreTrait
{
    /**
     * Store a piece of data in storage.
     *
     * These method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it  
     * can be also used by providers to store any other useful data (i.g., user_id, auth_nonce, etc.)
     *
     * @param string $token
     * @param mixed  $value
     *
     * @return mixed
     */
    public function storeData($name, $value = null)
    {
        // if empty, we simply delete the thing as we'd want to only store necessary data
        if (empty($value)) {
            return $this->deleteStoredData($name);
        }

        $this->getStorage()->set($this->providerId.'.'.$name, $value);
    }

    /**
     * Retrieve a piece of data from storage.
     *
     * These method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it  
     * can be also used by providers to retrieve from store any other useful data (i.g., user_id, auth_nonce, etc.)
     *
     * @param string $token
     *
     * @return mixed
     */
    public function getStoredData($name)
    {
        return $this->getStorage()->get($this->providerId.'.'.$name);
    }

    /**
     * Delete a stored piece of data.
     *
     * @param string $name
     */
    protected function deleteStoredData($name)
    {
        $this->getStorage()->delete($this->providerId.'.'.$name);
    }

    /**
     * Delete all stored data of the instantiated adapter
     */
    public function clearStoredData()
    {
        $this->getStorage()->deleteMatch($this->providerId.'.');
    }
}
