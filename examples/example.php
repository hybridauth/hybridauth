<?php
/*!
* This simple example illustrate how to authenticate users with GitHub.
*
* Most providers works pretty much the same. For more information, refer to the
* on-line user manual.
*/

/**
* Step 1: Require the Hybridauth Library
*
* If you are not using Composer, you may use the included Hybridauth PSR-4 compliant 
* autoloader as an example.
*
* If you are already using another PSR-4 autoloader on your project, simply map the
* `Hybridauth\\` namespace to the `./src` folder.
*/

include 'vendor/autoload.php'; // or include 'hybridauth_autoload.php';


/**
* Step 2: Configuring Your Application
*
* If you're already familiar with the process, you can skip the explanation below.
*
* To get started with GitHub authentication, you need to create a new GitHub
* application. 
*
* First, navigate to https://github.com/settings/applications then click the Register
* new application button at the top right of that page and fill in any required fields
* such as the application name, description and website.
*
* Set the Authorization callback URL to https://path/to/hybridauth/examples/example.php.
* Understandably, you need to replace 'path/to/hybridauth' with the real path to this
* script.
*
* After configuring your GitHub application, simple replace 'your-app-id' and 'your-app-secret'
* with your application credentials (Client ID and Client Secret).
*
* Providers who uses OAuth 2.0 protocol (i.g., GitHub, Facebook, Google, etc.) may need 
* an Authorization scope as additional parameter. Authorization scopes are strings that 
* enable access to particular resources, such as user data.
*
* https://developer.github.com/v3/oauth/
* https://developer.github.com/v3/oauth/#scopes
*/

$config = [
	'callback' => 'https://path/to/hybridauth/examples/example.php',

	'keys' => [ 'id' => 'your-app-id', 'secret' => 'your-app-secret' ],

	'scope' => [ 'user:email' ]
];


/**
* Step 3: Instantiate Github Adapter
*
* This example instantiates a GitHub adapter using the array $config we just built.
*/

$github = new Hybridauth\Provider\GitHub( $config );


/**
* Step 4: Logging Users In
*
* When invoked, `authenticate()` will redirect users to GitHub login page where they 
* will be asked to grant access to your application. If hey do, GitHub will redirect 
* the users back to Authorization callback URL (i.e., this script).
*
* Note that GitHub and few other providers will ask their users for authorisation
* only once. 
*/

$github->authenticate();


/**
* Step 5: Retrieve Users Profiles
*
* 
*/

$userProfile = $github->getUserProfile();

echo 'Hi ' . $userProfile->displayName;


/**
* Bonus: Access GitHub API
*
* List the authenticated user's public gists.
*/

$apiResponse = $github->apiRequest( '/gists/public' );


/**
* Step 6: Log Users Out
*
* 
*/

$github->disconnect();


/**
* Final note: Catching Exceptions
*
* Hybridauth use exceptions extensively and it's important that these exceptions
* be properly caught/handled in your code.
*
* Below is a basic example of how to catch exceptions.
*
* Note that on the previous step we disconnected the user. Meaning Hybridauth
* has erased the oauth access token used to sign http requests from the current 
* session. Thus, any new request to the provider API will now throw an exception.
*/

try{
	$github->getUserProfile();
}

/** 
* Catch Curl Errors
*
* This kind of error may happen when:
*     - Your internet connection is so bad.
*     - Your server configuration is also bad.
*     - We kidding. The full list of curl errors that may happen can be found at
*       http://curl.haxx.se/libcurl/c/libcurl-errors.html
*/
catch( Hybridauth\Exception\HttpClientFailureException $e ){
	echo 'Curl text error message : ' . $github->getHttpClient()->getResponseClientError();
}

/**
* Catch API Requests Errors
*
* This usually happen when requesting a:
*     - Wrong URI or a mal-formatted http request.
*     - Protected resource without providing a valid access token.
*/
catch( Hybridauth\Exception\HttpRequestFailedException $e ){
	echo 'Raw API Response: ' . $github->getHttpClient()->getResponseBody();
}

/**
* I catch everything else
*/
catch( \Exception $e ){
	echo 'Oops! We ran into an issue: ' . $e->getMessage();
}
