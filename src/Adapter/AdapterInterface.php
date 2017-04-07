<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

/**
 *
 */
interface AdapterInterface
{
    /**
    * Initiate the appropriate protocol and process/automate the authentication or authorization flow.
    *
    * @throws Exception
    * @throws RuntimeException
    * @throws UnexpectedValueException
    * @throws InvalidArgumentException
    * @throws AuthorizationDeniedException
    * @throws HttpClientFailureException
    * @throws HttpRequestFailedException
    * @throws InvalidAccessTokenException
    * @throws InvalidApplicationCredentialsException
    * @throws InvalidAuthorizationCodeException
    * @throws InvalidAuthorizationStateException
    * @throws InvalidOauthTokenException
    * @throws InvalidOpenidIdentifierException
    * @throws UnexpectedApiResponseException
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
    * @throws HttpClientFailureException
    * @throws HttpRequestFailedException
    * @throws UnexpectedApiResponseException
    * @throws UnexpectedValueException
    * @throws UnexpectedApiResponseException
    *
    * @return \Hybridauth\User\Profile
    */
    public function getUserProfile();

    /**
    * Retrieve the connected user contacts list
    *
    * @throws HttpClientFailureException
    * @throws HttpRequestFailedException
    * @throws UnexpectedApiResponseException
    * @throws UnexpectedValueException
    * @throws NotImplementedException
    * @throws UnexpectedApiResponseException
    *
    * @return array of \Hybridauth\User\Contact
    */
    public function getUserContacts();

    /**
    * return the user activity stream
    *
    * @param string $stream
    *
    * @throws HttpClientFailureException
    * @throws HttpRequestFailedException
    * @throws UnexpectedApiResponseException
    * @throws UnexpectedValueException
    * @throws NotImplementedException
    * @throws UnexpectedApiResponseException
    *
    * @return array of \Hybridauth\User\Activity
    */
    public function getUserActivity($stream);

    /**
    * Post a status on user wall|timeline|blog|website|etc.
    *
    * @param string|array $status
    *
    * @throws HttpClientFailureException
    * @throws HttpRequestFailedException
    * @throws UnexpectedApiResponseException
    * @throws UnexpectedValueException
    * @throws NotImplementedException
    * @throws UnexpectedApiResponseException
    *
    * @return mixed API response
    */
    public function setUserStatus($status);

    /**
    * Send a signed request to provider API
    *
    * @throws HttpClientFailureException
    * @throws HttpRequestFailedException
    * @throws UnexpectedApiResponseException
    * @throws UnexpectedValueException
    * @throws NotImplementedException
    *
    * @return \Hybridauth\User\Profile
    */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = []);
}
