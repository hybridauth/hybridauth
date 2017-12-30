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
$twitter->setUserStatus([
    'status' => 'Hello world!',
    'picture' => 'https://example.com/logo.png',
]);
</pre>

<hr />

**Tumblr example :**

<pre>
// Instantiate Tumblr Adapter.
$tumblr = new Hybridauth\Provider\Tumblr($config);

// Authenticate the user.
$tumblr->authenticate();

// Post a blog post.
$tumblr->setUserStatus("Hello world!");
</pre>

<hr />

**Facebook example :**

<pre>
// Instantiate Facebook Adapter.
$facebook = new Hybridauth\Provider\Facebook($config);

// Authenticate the user.
$facebook->authenticate();

// Update the user status.
$facebook->setUserStatus([
    'message' => 'Hello world!',
    'link'    => 'https://example.com/link/to/page',
]);
</pre>

> As of April 18, 2017, the (picture, name, caption, description) parameters are no longer supported by Graph API versions 2.9 and higher. As solution, you can add OG tags to page which is going to be posted like this:
```html
<meta property="og:url" content="https://hybridauth.github.io" />
<meta property="og:type" content="article" />
<meta property="og:title" content="Hybridauth Social Login PHP Library" />
<meta property="og:description" content="Welcome to Hybridauth documentation" />
<meta property="og:image" content="https://example.com/logo.png" />
```
> More info here: https://developers.facebook.com/docs/sharing/webmasters/

<hr />

**LinkedIn example :**

LinkedIn supports few [extra parameters](https://developer.linkedin.com/docs/share-on-linkedin) when posting a new user status:

<pre>
// Instantiate LinkedIn Adapter.
$linkedin = new Hybridauth\Provider\LinkedIn($config);

// Authenticate the user.
$linkedin->authenticate();

// Update the user status.
$linkedin->setUserStatus([
    'comment' => 'Check out developer.linkedin.com!',
    'content' => [
        'title' => 'LinkedIn Developers Resources',
        'description' => "Leverage LinkedIn's APIs to maximize engagement",
        'submitted-url' => 'https://developer.linkedin.com',
        'submitted-image-url' => 'https://example.com/logo.png',
    ],
    'visibility' => [
        'code' => 'anyone',
     ],
]);
</pre>
