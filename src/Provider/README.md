#### Upgrading supported providers to 3.0

##### 1. OpenID

OpenID providers are still the same and they are the most easy ones: in most cases we just set $openidIdentifier and that's it.

```php
namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;

class Stackoverflow extends OpenID
{
	protected $openidIdentifier = 'https://openid.stackexchange.com/';
}
```

##### 2. OAuth 2

OAuth2Client and Model_OAuth2 are now merged together into a new abstract class to simplify the authorization flow.

Subclasses (i.e., providers adapters) can either use the already provided methods by the new OAuth2 class, override them, or create new ones when needed.

```php
namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception;
use Hybridauth\Data;
use Hybridauth\User;

class MyCustomProvider extends OAuth2
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
			"client_id"     => $this->clientId,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->endpoint
		];
		$this->tokenExchangeMethod  = 'POST';
		$this->tokenExchangeHeaders = [ 'Authorization' => 'Basic ' . base64_encode( $this->clientId .  ':' . $this->clientSecret ) ];

		/* optional: add any extra parameters or headers when sending signed requests */
		$this->apiRequestParameters = [ 'access_token'  => $this->token( "access_token" ) ];
		$this->apiRequestHeaders    = [ 'Authorization' => 'Bearer ' . $this->token( "access_token" ) ];
	} 

	function getUserProfile()
	{
		try
		{
			/* now we use apiRequest() for signed requests. */
			$response = $this->apiRequest( 'user/profile', 'GET', [], [ 'Authorization' => .. ] );

			$collection = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			/* apiRequest() will throw an exception when a request fails. raw api response can be inspected via self::httpClient. */
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		/* example of how to instantiate a user profile and how to use data collection, assuming user/profile returns this response:

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

		$userProfile = new User\Profile();

		$userProfile->identifier = $collection->get( 'id' );

		$userProfile->email = $collection->get( 'email' );

		$userProfile->displayName = $data->get( 'firstName' ) . ' ' . $data->get( 'lastName' ) ;

		if( $collection->exists( 'image' ) )
		{
			$userProfile->photoURL = 'http://provider.ltd/users/' . $collection->get( 'image' );
		}

		$userProfile->address = $collection->filter( 'address' )->get( 'streetAddress' );

		$userProfile->city = $collection->filter( 'address' )->get( 'city' );

		// ...

		return $userProfile;
	}

	//..
}
```

##### 3. OAuth 1

OAuth 1 is very similar to OAuth 2. Subclasses can set the default provider endpoints and settings.

```php
namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth1;
use Hybridauth\Exception;
use Hybridauth\Data;
use Hybridauth\User;

class MyCustomProvider extends OAuth1
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
	protected $accessTokenUrl = 'https://api.provider.ltd/oauth/request_token';

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
		$this->apiRequestParameters = [ 'access_token' => $this->token( "access_token" ) ];
		$this->apiRequestHeaders    = [ 'Accept-Encoding' => 'compress, gzip' ];
	}

	//..
}
```

### List of done adapters - but UNTESTED still

##### OpenID

* OpenID
* AOL
* PaypalOpenID
* Stackoverflow (new)
* YahooOpenID
* Steam

##### OAuth 1

* Twitter
* 500px
* Tumblr

##### OAuth 2

* Google
* Disqus
* Facebook
* Foursquare
* Freeagent
* GitHub
* Instagram
* PixelPin
* TwitchTV
* WordPress (new)
* Reddit (new)
* Dribbble (new)

#### Pending list

* Deezer
* Goodreads
* LinkedIn
* Live
* Mailru
* Murmur
* Odnoklassniki
* Pixnet
* Plurk
* QQ
* Skyrock
* Vkontakte
* XING
* Yahoo
* Yandex

### Dropped providers - due to external library requirements 

* Vimeo
* Viadeo
* Sina
* Last.fm
* Draugiem
