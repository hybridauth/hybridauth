---
layout: default
title: "Introduction to Hybridauth & Basic Usage"
description: "Contains introductory examples of how to use Hybridauth to sign in users with social networks."
---

Introduction & Basic Usage
==========================

{% include callout.html content="This section assume you've already installed Hybridauth 3 in your project and successfully integrated the appropriate autoloader in your script to auto-detect the proper location of Hybridauth source code to load all the required classes automatically. If not, you may want to head back to [Installation](install.html)." type="primary" %} 

### Introduction

Hybridauth enables developers to easily build social applications and tools to engage websites visitors and customers on a social level that starts off with social sign-in and extends to social sharing, users profiles, friends lists, activities streams, status updates and more. The main goal of Hybridauth is to act as an abstract API between your application and the various social networks APIs and identities providers such as Facebook, Twitter and Google.

At a technical level, Hybridauth implements a number of open protocols (i.g, OAuth and OpenID) to connect and communicate with various social networks APIs in a secure and standardised manner. Given you're granted the right authorizations by a social network user, Hybridauth may access the said API to perform a wide range of operations in behalf of that user (i.g., retrieve user's profile, post a status update).

To sign in users with social networks or identity providers (Often called providers or IDPs for short) using Hybridauth within your website, it's possible to **either use** the **unified interface** the library provides, or to **instantiate the provider's adapter** directly. Main difference is the former will provide a number of extra methods as helpers to manage multiple providers at once while the latter allows you to tidy up your code for a specific provider.

{% include callout.html content="A provider or IDP means a social network, identity provider or authentication service. An adapter is the actual PHP class implemented by Hybridauth to abstract a provider's API." type="default" %} 

### Instantiating Provider's Adapter

In this this simple example we'll illustrate how to authenticate users with **Facebook**, however all the other supported providers work pretty much the same. Generally speaking, it's a matter of replacing the provider adapter's name and its application's credentials.

First step is to build a configuration array containing your Facebook application credentials and an authorized callback. Next is to instantiate Facebook's adapter, providing that configuration array. And then we may proceed and sign in with Facebook.

{% include note.html content="This assumes you have already created and configured a Facebook application, which you can obtain from their [App Dashboard](https://developers.facebook.com/apps)." %} 

<pre>
//Build configuration array
$config = [
    //Location where to redirect users once they authenticate with Facebook
    //For this example we choose to come back to this same script
    'callback' => 'https://example.com/path/to/this/script.php',

    //Facebook application credentials
    'keys' => [
        'id'     => '...', //Required: your Facebook application id
        'secret' => '...'  //Required: your Facebook application secret 
    ]
];

try {
    //Instantiate Facebook's adapter directly
    $adapter = new Hybridauth\Provider\Facebook($config);

    //Attempt to authenticate the user with Facebook
    $adapter->authenticate();

    //Returns a boolean of whether the user is connected with Facebook
    $isConnected = $adapter->isConnected();
 
    //Retrieve the user's profile
    $userProfile = $adapter->getUserProfile();

    //Inspect profile's public attributes
    var_dump($userProfile);

    //Disconnect the adapter 
    $adapter->disconnect();
}
catch(\Exception $e){
    echo 'Oops, we ran into an issue! ' . $e->getMessage();
}
</pre>

### Unified Interface

For ease of use of multiple providers, Hybridauth implements the class `Hybridauth\Hybridauth`, a sort of factory/façade which acts as an unified interface or entry point, and it expects a configuration array containing the list of providers you want to use, their respective credentials and authorized callback.

<pre>
//First step is to build a configuration array to pass to `Hybridauth\Hybridauth`
$config = [
    //Location where to redirect users once they authenticate with a provider
    'callback' => 'https://example.com/path/to/this/script.php',

    //Providers specifics
    'providers' => [
        'Twitter' => [ 
            'enabled' => true,     //Optional: indicates whether to enable or disable Twitter adapter. Defaults to false
            'keys' => [ 
                'key'    => '...', //Required: your Twitter consumer key
                'secret' => '...'  //Required: your Twitter consumer secret
            ]
        ],
        'Google'   => ['enabled' => true, 'keys' => [ 'id'  => '...', 'secret' => '...']], //To populate in a similar way to Twitter
        'Facebook' => ['enabled' => true, 'keys' => [ 'id'  => '...', 'secret' => '...']]  //And so on
    ]
];

try{
    //Feed configuration array to Hybridauth
    $hybridauth = new Hybridauth\Hybridauth($config);

    //Then we can proceed and sign in with Twitter as an example. If you want to use a diffirent provider, 
    //simply replace 'Twitter' with 'Google' or 'Facebook'.

    //Attempt to authenticate users with a provider by name
    $adapter = $hybridauth->authenticate('Twitter'); 

    //Returns a boolean of whether the user is connected with Twitter
    $isConnected = $adapter->isConnected();
 
    //Retrieve the user's profile
    $userProfile = $adapter->getUserProfile();

    //Inspect profile's public attributes
    var_dump($userProfile);

    //Disconnect the adapter 
    $adapter->disconnect();
}
catch(\Exception $e){
    echo 'Oops, we ran into an issue! ' . $e->getMessage();
}
</pre>

<hr />

As noted above, the class `Hybridauth\Hybridauth` provides a number of extra methods to help you manage multiple connected providers.  

**Examples:**

<pre>
//Retrieve back the configuration used for provider by name
$array = $hybridauth->getProviderConfig('Twitter');

//Returns a boolean of whether the user is connected with a provider by name
$boolean = $hybridauth->isConnectedWith('Google');

//Returns a new instance of a provider's adapter by name
$adapter = $hybridauth->getAdapter('Facebook');

//Returns a list of new instances of currently connected adapters
$array = $hybridauth->getConnectedAdapters();

//Returns a list of currently connected adapters names
$array = $hybridauth->getConnectedProviders();

//Disconnect all currently connected adapters
$hybridauth->disconnectAllAdapters();
</pre>
