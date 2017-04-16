<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Logger\LoggerInterface;

/**
 *
 */
interface AdapterInterface
{
    /**
    * Initiate the appropriate protocol and process/automate the authentication or authorization flow.
    *
    * @return boolean|null
    */
    public function authenticate();

    /**
    * Returns TRUE if the user is connected
    *
    * @return boolean
    */
    public function isConnected();

    /**
    * Clear all access token in storage
    *
    * @return boolean
    */
    public function disconnect();

    /**
    * Retrieve the connected user profile
    *
    * @return \Hybridauth\User\Profile
    */
    public function getUserProfile();

    /**
    * Retrieve the connected user contacts list
    *
    * @return array of \Hybridauth\User\Contact
    */
    public function getUserContacts();

    /**
    * return the user activity stream
    *
    * @param string $stream
    *
    * @return array of \Hybridauth\User\Activity
    */
    public function getUserActivity($stream);

    /**
    * Post a status on user wall|timeline|blog|website|etc.
    *
    * @param string|array $status
    *
    * @return mixed API response
    */
    public function setUserStatus($status);

    /**
    * Send a signed request to provider API
    *
    * @return mixed
    */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = []);

    /**
     * Return oauth access tokens.
     *
     * @return array
     */
    public function getAccessToken();

    /**
     * Set oauth access tokens.
     */
    public function setAccessToken($tokens = []);

    /**
     * Set http client instance.
     */
    public function setHttpClient(HttpClientInterface $httpClient = null);

    /**
     * Return http client instance.
     */
    public function getHttpClient();

    /**
     * Set storage instance.
     */
    public function setStorage(StorageInterface $storage = null);

    /**
     * Return storage instance.
     */
    public function getStorage();

    /**
     * Set Logger instance.
     */
    public function setLogger(LoggerInterface $logger = null);

    /**
     * Return logger instance.
     */
    public function getLogger();
}
