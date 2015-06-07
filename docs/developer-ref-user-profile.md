User profile
============

After authentication, HybridAuth provide the connected user profile's in a rich, simple and **standardized structure** across all the social APIs.

Also, keep in mind that not all social APIs will provide all the user data to third-party.

The `Hybridauth\User\Profile` object will be populated with as much information about the user as HybridAuth was able to pull from the given API or authentication provider.

**Sample :**

```php
// Instantiate Github Adapter
$github = new Hybridauth\Provider\GitHub($config);

// Aauthenticate with the user
$github->authenticate();

// Retrieve Users Profiles
$userProfile = $github->getUserProfile();

echo 'Hi '.$userProfile->displayName;
```

### Class Hybridauth\User\Profile

It represents the current logged in user profile. list of fields available in the normalized user profile structure used by HybridAuth. 

**Data Members :**

Field Name    | Type     | Short description
------------- | ---------| -------------------------------------------------------
identifier    | String   | The Unique user's ID on the connected provider. Can be an integer for some providers, Email, URL, etc.
profileURL    | String   | URL link to profile page on the IDp web site
webSiteURL    | String   | User website, blog, web page,
photoURL      | String   | URL link to user photo or avatar
displayName   | String   | User dispalyName provided by the IDp or a concatenation of first and last name.
description   | String   | A short about_me
firstName     | String   | User's first name
lastName      | String   | User's last name
gender        | String   | User's gender. Values are 'female', 'male' or NULL
language      | String   | User's language
age           | Integer  | User' age, note that we don't calculate it. we return it as is if the IDp provide it
birthDay      | Integer  | The day in the month in which the person was born.
birthMonth    | Integer  | The month in which the person was born.
birthYear     | Integer  | The year in which the person was born.
email         | String   | User email. Not all of IDp grant access to the user email
emailVerified | String   | Verified user email. Note: not all of IDp grant access to verified user email. Currently only Facebook, Google, Yahoo and Foursquare do provide the verified user email.
phone         | String   | User's phone number
address       | String   | User's address
country       | String   | User's country
region        | String   | User's state or region 
city          | String   | User's city
zip           | Integer  | Postal code or zipcode.
