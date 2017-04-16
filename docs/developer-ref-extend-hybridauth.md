---
layout: default
title: "Extending Hybridauth - Adding new providers"
description: "Describes how to add new providers adapters to Hybridauth, and how to port them from 2.x."
---

# Upgrading supported providers to 3.0

## 1. OpenID

OpenID providers are still the same and they are the most easy ones: in most cases we just set $openidIdentifier and that's it.

<pre>
namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;

final class StackoverflowOpenID extends OpenID
{
    protected $openidIdentifier = 'https://openid.stackexchange.com/';
}
</pre>

## 2. OAuth 2

`OAuth2Client` and `Model_OAuth2` are now merged together into a new abstract class to simplify the authorization flow.

Subclasses (i.e., providers adapters) can either use the already provided methods by the new OAuth2 class, override them,
or create new ones when needed.

<pre>
namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

final class MyCustomProvider extends OAuth2
{
    /**
    * Defaults scope to requests 
    */
    protected $scope = 'email, ...';

    /**
    * Default Base URL to provider API
    */
    protected $apiBaseUrl = 'https://api.provider.ltd/version/';

    /**
    * Default Authorization Endpoint
    */
    protected $authorizeUrl = 'https://api.provider.ltd/oauth/authorize';

    /**
    * Default Access Token Endpoint
    */
    protected $accessTokenUrl = 'https://api.provider.ltd/oauth/access_token';

    /* optional: set any extra parameters or settings */
    protected function initialize()
    {
        parent::initialize();

        /* optional: determine how exchange Authorization Code with an Access Token */
        $this->tokenExchangeParameters = [
            'client_id'     => $this->clientId,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->endpoint
        ];
        $this->tokenExchangeMethod  = 'POST';
        $this->tokenExchangeHeaders = [ 'Authorization' => 'Basic ' . base64_encode( $this->clientId .  ':' . $this->clientSecret ) ];

        /* optional: add any extra parameters or headers when sending signed requests */
        $this->apiRequestParameters = [ 'access_token'  => $this->token( 'access_token' ) ];
        $this->apiRequestHeaders    = [ 'Authorization' => 'Bearer ' . $this->token( 'access_token' ) ];
    } 

    function getUserProfile()
    {
        /* Send a signed http request to provider API to request user's profile */
        $response = $this->apiRequest( 'user/profile' );

        /* Example of how to instantiate a user profile and how to use data collection
           assuming user/profile returns this response:
            {
                "id": "98131543",
                "firstName": "John",
                "lastName": "Smith",
                "age": 25,
                "emails : "John.Smith@domain.ltd",
                "address": {
                    "streetAddress": "21 2nd Street",
                    "city": "New York",
                    "state": "NY",
                    "postalCode": "10021-3100"
                }
            }
        */

        $collection = new Data\Collection( $response );

        $userProfile = new User\Profile();

        if( ! $data->exists( 'id' ) )
        {
            throw new UnexpectedValueException( 'Provider API returned an unexpected response.' );
        }

        $userProfile->identifier = $collection->get( 'id' );
        $userProfile->email = $collection->get( 'email' );
        $userProfile->displayName = $data->get( 'firstName' ) . ' ' . $data->get( 'lastName' ) ;
        $userProfile->address = $collection->filter( 'address' )->get( 'streetAddress' );
        $userProfile->city = $collection->filter( 'address' )->get( 'city' );

        if( $collection->exists( 'image' ) )
        {
            $userProfile->photoURL = 'http://provider.ltd/users/' . $collection->get( 'image' );
        }

        return $userProfile;
    }

    //..
}
</pre>

## 3. OAuth 1

OAuth 1 is very similar to OAuth 2. Subclasses can set the default provider endpoints and settings.

<pre>
namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth1;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

final class MyCustomProvider extends OAuth1
{
    /**
    * Default Base URL to provider API
    */
    protected $apiBaseUrl = 'https://api.provider.ltd/version/';

    /**
    * Default Authorization Endpoint
    */
    protected $authorizeUrl = 'https://api.provider.ltd/oauth/authorize';

    /**
    * Unauthorized Request Token Endpoint
    */
    protected $requestTokenUrl = 'https://api.provider.ltd/oauth/request_token';

    /**
    * Default Access Token Endpoint
    */
    protected $accessTokenUrl = 'https://api.provider.ltd/oauth/access_token';

    /* optional: set any extra parameters or settings */
    protected function initialize()
    {
        parent::initialize();

        /* optional: define a how request Unauthorized OAuth Token */
        $this->requestTokenMethod     = 'GET'; 
        $this->requestTokenParameters = [ .. ];
        $this->requestTokenHeaders    = [ .. ]; 

        /* optional: determine how the exchange Unauthorized OAuth Token with an Access Token */
        $this->tokenExchangeMethod     = 'POST'; 
        $this->tokenExchangeParameters = [ .. ]; 
        $this->tokenExchangeHeaders    = [ .. ];

        /* optional: add any extra parameters or headers when sending signed requests */
        $this->apiRequestParameters = [ 'access_token'    => $this->token( 'access_token' ) ];
        $this->apiRequestHeaders    = [ 'Accept-Encoding' => 'compress, gzip' ];
    }

    //..
}
</pre>
