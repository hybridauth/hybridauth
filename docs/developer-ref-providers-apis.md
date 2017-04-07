---
layout: default
title: "Access Providers APIs"
description: "Outlines how to access social networks APIs in behalf of connected users."
---

Access Providers APIs
=====================

Once a user is authenticated with an OAuth1 or OAuth2 provider, and depending on the authorization given to your application, you should be able to query the that provider's API in behalf of the connected user.

For this you can call the method `apiRequest` on a connected adapter instance, and it's defined as follows:

<pre>
function apiRequest(string $url [, string $method [, array $parameters [, array $headers]]])
</pre>

Parameter     | Type   | Description
------------- | ------ | -----------------------------------------------------------------------------------
`$url       ` | string | Required: Relative or absolute url to API's action.
`$method    ` | string | Optional: HTTP method. It can be either **GET** or **POST**, and defaults to GET.
`$parameters` | array  | Optional: HTTP GET parameters or POST data as an associative array.
`$headers   ` | array  | Optional: HTTP REQUEST Headers as an associative array.

{% include callout.html content="OpenID being a decentralized authentication protocol and not an authorization standard, its adapters do not communicate with providers APIs and therefore you cannot perform any action in behalf of authenticated user, and Hybridauth will throw `NotImplementedException` when `apiRequest` is called. To know more about providers specs and capabilities, refer to [Supported Providers](providers.html) . " type="primary" %}


**Usage**

Below is a simple example of how to access GitHub's API.

<pre>
//Instantiate GitHub's adapter
$github = new Hybridauth\Provider\GitHub($config);

//Authenticate the user
$github->authenticate();

//Access GitHub API to retrieve the user's public gists.
//See: https://developer.github.com/v3/gists/
$apiResponse = $github->apiRequest('gists'); // or absolute url: https://api.github.com/gists

//Inspect API's response.
var_dump($apiResponse);
</pre>

<hr />

Another simple example of how to access Twitter's API.

<pre>
//Instantiate Twitter's adapter
$twitter = new Hybridauth\Provider\Twitter($config);

//Authenticate the user
$twitter->authenticate();

//Access Twitter's API to post a status update
//See: https://dev.twitter.com/rest/reference/post/statuses/update
$apiResponse = $twitter->apiRequest('statuses/update.json', 'POST', [ 'status' => 'This is tests!' ]);

//Inspect API's response.
var_dump($apiResponse);
</pre>
