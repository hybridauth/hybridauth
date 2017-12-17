---
layout: default
title: "User's Contacts"
description: "Describes how to retrieve users contacts lists."
---

User's Contacts
===============

Similarly to the user's profile, after authentication, Hybridauth can provide the connected user contact
list in a rich, simple and **standardized structure** across the social APIs supporting this feature.

If a provider does not support this feature, Hybridauth will throw an exception `NotImplementedException`.
To know more about providers capabilities, refer to [Supported Providers](providers.html) .

**Usage :**

<pre>
//Instantiate Google Adapter
$google = new Hybridauth\Provider\Google($config);

//Sign in with google
$google->authenticate();

//Retrieve User's contacts
$userContacts = $google->getUserContacts(); //Returns an array of Hybridauth\User\Contact objects

//Iterate over the user contacts list
foreach( $userContacts as $contact ){
	echo $contact->displayName . ' ' . $contact->profileURL . "\n";
}
</pre>


### Class Hybridauth\User\Contact

This class represents a user's contact.

**Data Members :**

Field Name    | Type     | Short description
------------- | ---------| -------------------------------------------------------
identifier    | String   | The Unique contact's ID on the connected provider. Usually an integer.
profileURL    | String   | URL link to profile page on the IDp web site
webSiteURL    | String   | User website, blog, web page, etc.
photoURL      | String   | URL link to user photo or avatar
displayName   | String   | User dispalyName provided by the IDp or a concatenation of first and last name.
description   | String   | A short about_me or the last contact status
email         | String   | User email. *Not all of IDp grant access to the user email*
