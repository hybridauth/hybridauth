---
layout: default
title: "Supported providers"
description: "Listing of supported social networks and identity providers and their enabled features."
---

Supported providers
===================

The table below lists the social networks and identity providers currently supported by Hybridauth 3 and outlines their enabled features (or capabilities).

{% include callout.html content="A provider or IDP means a social network, identity provider or authentication service. An adapter is the actual PHP class implemented by Hybridauth to abstract a provider's API." type="primary" %} 

{% include callout.html content="While OpenID providers do not require an application [[1]](http://openid.net/specs/openid-connect-core-1_0.html#Overview) and thus no action from your part to make their adapters work, OAuth providers requires consumer (or client) websites (i.g., your website) to register an application [[2]](http://tools.ietf.org/html/rfc5849#page-3) [[3]](http://tools.ietf.org/html/rfc6749#section-2). Generally speaking those providers will have a dedicated developer's section or subdomain where you can register yours and apply for a pair of keys (i.e, application credentials )." type="primary" %} 

Adapter Unique Name | Specs   | Authentication | User's Profile | User's Contacts | User's Status  | User's Activity Stream
------------------- | ------- | -------------- | -------------- | --------------- | -------------- | ----------------------
Facebook            | OAuth2  | [X]            | [X]            | [X]             | [X]            | [X]                  
Twitter             | OAuth1  | [X]            | [X]            | [X]             | [X]            | [X]                  
Google              | OAuth2  | [X]            | [X]            | [X]             |                |                      
GitHub              | OAuth2  | [X]            | [X]            |                 |                |                      
GitLab              | OAuth2  | [X]            | [X]            |                 |                |                      
Reddit              | OAuth2  | [X]            | [X]            |                 |                |                      
BitBucket           | OAuth2  | [X]            | [X]            |                 |                |                      
WordPress           | OAuth2  | [X]            | [X]            |                 |                |                      
Tumblr              | OAuth1  | [X]            | [X]            | [X]             |                |                      
Disqus              | OAuth2  | [X]            | [X]            |                 |                |                      
Dribbble            | OAuth2  | [X]            | [X]            |                 |                |                      
WindowsLive         | OAuth2  | [X]            | [X]            | [X]             |                |                      
Foursquare          | OAuth2  | [X]            | [X]            | [X]             |                |                      
Instagram           | OAuth2  | [X]            | [X]            |                 |                |                      
LinkedIn            | OAuth2  | [X]            | [X]            |                 |                |                      
Yahoo               | OAuth2  | [X]            | [X]            |                 |                |                      
Vkontakte           | OAuth2  |                |                |                 |                |                      
Mailru              | OAuth2  |                |                |                 |                |                      
Odnoklassniki       | OAuth2  | [X]            | [X]            |                 |                |                      
StackExchange       | OAuth2  | [X]            | [X]            |                 |                |                      
OpenID              | OpenID  | [X]            | [X]            |                 |                |                      
PaypalOpenID        | OpenID  | [X]            | [X]            |                 |                |                      
StackExchangeOpenID | OpenID  | [X]            | [X]            |                 |                |                      
YahooOpenID         | OpenID  | [X]            | [X]            |                 |                |                      
AOLOpenID           | OpenID  | [X]            | [X]            |                 |                |                      
Steam               | Hybrid  | [X]            | [X]            |                 |                |                      
Discord             | OAuth2  | [X]            | [X]            |                 |                |                      
TwitchTV            | OAuth2  | [X]            | [X]            |                 |                |                      
Authentiq           | OAuth2  | [X]            | [X]            |                 |                |                      
Spotify             | OAuth2  | [X]            | [X]            |                 |                |                      

{% include callout.html content="Some providers such as Google and Yahoo may use multiple protocols for their APIs and as naming convention we append the protocol's name to the adapter's (Often the case with OpenID adapters as those might be subject to removal by providers in near future due to deprecation of the OpenID protocol)." type="default" %} 

<script>
$(function () {
  $("td:contains('[X]')").each(function() {
    var replaced = $(this).html().replace(/\[X\]/g, '<i class="fa fa-check-square fa-2"></i>');
    $(this).html(replaced);
  });
});
</script>
