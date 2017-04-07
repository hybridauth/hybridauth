---
layout: default
title: "User's Activity Stream"
description: "Describes how to retrieve users activity stream such as tweets and Facebook’s wall."
---

User's Activity Stream
======================

Similarly to the user's profile, after authentication, Hybridauth can provide the connected user activity
stream in a simple and **standardized structure** across the social APIs supporting this feature.

If a provider does not support this feature, Hybridauth will throw an exception `NotImplementedException`.
To know more about providers capabilities, refer to [Supported Providers](providers.html) .

**Usage :**

<pre>
//Instantiate Twitter Adapter
$twitter = new Hybridauth\Provider\Twitter($config);

//Sign in with twitter
$twitter->authenticate();

//Retrieve User's latest tweets
$timeline = $adapter->getUserActivity('me'); //Returns an array of Hybridauth\User\Activity objects

//Iterate over the user's timeline
foreach($timeline as $item){
    echo $item->user->displayName . ': ' . $item->text . "\n";
}
</pre>


### Class Hybridauth\User\Activity

This class represents a user's activity.

**Data Members :**

Field Name    | Type     | Short description
------------- | ---------| -------------------------------------------------------
id            | String   | Event ID on the provider side
date          | String   | Event date of creation. provided as is for now.
text          | String   | Activity/event/story content as string.
user	      | stdClas  | User owner of activity. <small>See section below for its structure.</small> 

#### Sub class Hybridauth\User\Activity::user

This class represents a user's activity owner.

**Data Members :**

Field Name    | Type     | Short description
------------- | ---------| -------------------------------------------------------
identifier    | String   | The Unique user ID on the provider side. Usually an interger.
displayName   | String   | User dispalyName provided by the provider
profileURL    | String   | URL link to profile page on the IDp web site
photoURL      | String   | URL link to user photo or avatar

