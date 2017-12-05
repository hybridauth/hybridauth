---
layout: default
title: "Update User Status"
description: "Outlines how to update user’s status."
---

Update User Status
==================

Hybridauth provides a way to updates users statuses across the social APIs supporting this feature.

If a provider does not support this feature, Hybridauth will throw an exception `NotImplementedException`.
To know more about providers capabilities, refer to [Supported Providers](providers.html) .

**Twitter example :**

<pre>
// Instantiate Twitter Adapter.
$twitter = new Hybridauth\Provider\Twitter($config);

// Authenticate the user.
$twitter->authenticate();

// Update the user status.
$twitter->setUserStatus( "Hello world!" );
</pre>

<hr />

**Tumblr example :**

<pre>
// Instantiate Tumblr Adapter.
$tumblr = new Hybridauth\Provider\Tumblr($config);

// Authenticate the user.
$tumblr->authenticate();

// Post a blog post.
$tumblr->setUserStatus( "Hello world!" );
</pre>

<hr />

**Facebook example :**

Facebook supports few extra parameters when posting a new user status :

<pre>
// Instantiate Facebook Adapter.
$facebook = new Hybridauth\Provider\Facebook($config);

// Authenticate the user.
$facebook->authenticate();

// Update the user status.
$facebook->setUserStatus(
    array(
        'message' => 'Hello world!',
        'link'    => 'https://example.com/link/to/page',
        'picture' => 'https://example.com/link/to/picture.jpg'
    )
);
</pre>

**LinkedIn example :**

LinkedIn supports few extra parameters when posting a new user status :

<pre>
// Instantiate LinkedIn Adapter.
$linkedin = new Hybridauth\Provider\LinkedIn($config);

// Authenticate the user.
$linkedin->authenticate();

// Update the user status.
$linkedin->setUserStatus(
    array(
        'comment' => 'Check out developer.linkedin.com!',
        'content' => array(
            'title' => 'LinkedIn Developers Resources',
            'description' => "Leverage LinkedIn's APIs to maximize engagement",
            'submitted-url' => 'https://developer.linkedin.com',
            'submitted-image-url' => 'https://example.com/logo.png',
        ),
        'visibility' => array(
            'code' => 'anyone',
        ),
    )
);
</pre>
