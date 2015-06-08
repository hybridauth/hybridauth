User Contacts
=============

**Important** Currently only Facebook, Twitter and LinkedIn do support this feature. Others providers will throw an
exception (`NotImplementedException`: Provider does not support this feature), when `getUserContacts()` is called.


Same with the user's profile, after authentication, HybridAuth can provide the connected user contact list's in a rich,
simple and **standardized structure** across all the social APIs.

**Sample :**

```php
// Instantiate Google Adapter
$google = new Hybridauth\Provider\Google($config);

// Aauthenticate with the user
$google->authenticate();

// Retrieve Users Profiles
$userContacts = $google->getUserContatcs();

// Iterate over the user contacts list
foreach( $userContacts as $contact ){
	echo $contact->displayName . ' ' . $contact->profileURL . "\n";
}
```


### Class Hybridauth\User\Contact

This class represents the .... 

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
