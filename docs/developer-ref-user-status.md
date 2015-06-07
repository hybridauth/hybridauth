Update User Status
==================

**Important** Currently only Facebook, Twitter, Identica, LinkedIn, QQ, Sina, Murmur, Pixnet and Plurkg do support this feature. Others providers will throw an exception (#8 "Provider does not support this feature"), when `setUserStatus()` is called. Please refer to the user guide to know more about each adapters capabilities.


**Twitter example :**

```php
// Instantiate Twitter Adapter
$twitter = new Hybridauth\Provider\Twitter($config);

// Aauthenticate with the user
$twitter->authenticate();

// Update the user status
$twitter->setUserStatus( "Hello world!" );
```


**Facebook example :**

*Note:* For Facebook we can add even more information to the user update:

```php
// Instantiate Facebook Adapter
$facebook = new Hybridauth\Provider\Twitter($config);

// Aauthenticate with the user
$facebook->authenticate();

// Update the user status
$twitter->setUserStatus
(
    array
    (
        'message' => 'Hello world!',
        'link"    => 'https://example.com/link/to/page',
        'picture' => 'https://example.com/link/to/picture.jpg'
        )
    )
);
```
