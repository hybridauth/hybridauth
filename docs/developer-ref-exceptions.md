---
layout: default
title: "Exceptions Handling"
description: "Errors and exceptions handling."
---

Exceptions Handling
===================

Hybridauth uses exceptions extensively and it's important that these exceptions be properly caught/handled in your code.

**Example:**

<pre>
try {
    $github = new Hybridauth\Provider\GitHub($config);
    
    $github->authenticate();
    $github->getUserProfile();
}

/**
* Catch Curl Errors
*
* This kind of error may happen when:
*     - Internet or Networks issues.
*     - Your server configuration is not setup correctly.
* The full list of curl errors that may happen can be found at http://curl.haxx.se/libcurl/c/libcurl-errors.html
*/
catch (Hybridauth\Exception\HttpClientFailureException $e) {
    echo 'Curl text error message : ' . $github->getHttpClient()->getResponseClientError();
}

/**
* Catch API Requests Errors
*
* This usually happen when requesting a:
*     - Wrong URI or a mal-formatted http request.
*     - Protected resource without providing a valid access token.
*/
catch (Hybridauth\Exception\HttpRequestFailedException $e) {
    echo 'Raw API Response: ' . $github->getHttpClient()->getResponseBody();
}

/**
* This fellow will catch everything else
*/
catch (\Exception $e) {
    echo 'Oops! We ran into an unknown issue: ' . $e->getMessage();
}
</pre>

<br />
**Exceptions Inheritance Tree:**

<pre>
Exception
|   RuntimeException
|   |    UnexpectedValueException
|   |    |    AuthorizationDeniedException
|   |    |    HttpClientFailureException
|   |    |    HttpRequestFailedException
|   |    |    InvalidAuthorizationCodeException
|   |    |    InvalidAuthorizationStateException
|   |    |    InvalidOauthTokenException
|   |    |    InvalidAccessTokenException
|   |    |    UnexpectedApiResponseException
|   |
|   |    BadMethodCallException
|   |    |   NotImplementedException
|   |
|   |    InvalidArgumentException
|   |    |   InvalidApplicationCredentialsException
|   |    |   InvalidOpenidIdentifierException
</pre>

<br />
**Exceptions description:**


**Exception Name**                        | **Description**
----------------------------------------- | --------------------------------------------------------------------------------
`Exception                              ` | Exception is the base class for all Exceptions.
`RuntimeException                       ` | Exception thrown if an error which can only be found on runtime occurs.
`InvalidArgumentException               ` | Exception thrown if an argument is not of the expected type.
`UnexpectedValueException               ` | Exception thrown if a value does not match with a set of values. 
`BadMethodCallException                 ` | Exception thrown if a callback refers to an undefined method or if some arguments are missing.
`NotImplementedException                ` | Exception thrown when a requested method or operation is not implemented.
`InvalidApplicationCredentialsException ` | Exception thrown when your application key and secret are not setup correctly
`InvalidAuthorizationCodeException      ` | Exception thrown when an OAuth2 authentication request fails due to a missing, invalid, or mismatching redirection URI, or if the client identifier is missing or invalid.
`InvalidAuthorizationStateException     ` | Exception thrown when an OAuth2 authorization state is either invalid or has been used.
`InvalidAccessTokenException            ` | Exception thrown when an OAuth1 `access_token` is invalid. 
`UnexpectedApiResponseException         ` | 
`InvalidOauthTokenException             ` | Exception thrown when an OAuth1 `oauth_token` is invalid.
`InvalidOpenidIdentifierException       ` | Exception thrown when an OpenID identifier `openid_identifier` was not setup correctly.
`HttpClientFailureException             ` | Exception thrown when the curl http client returns an errors. Typically happen due to networks issues or server configuration.
`HttpRequestFailedException             ` | Exception thrown when a requested URL returns an errors. This usually happen when requesting a wrong URI or a protected resource without providing a valid access token.
`AuthorizationDeniedException           ` | Exception thrown when a user deny the authentication/authorization request.

