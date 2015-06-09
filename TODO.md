Hybridauth 3 Todo List
======================

#### overview

task                  | .
--------------------- | ----------
Core components       |  Completed
Implemented providers |      26/27
Working providers     |       7/27
Documentation         |       ~70%
Code coverage         |          0

-----------------------

### Required:

#### Core

Hybridauth core components has been entirely implemented, and while new modification may occur in near future, the design is
most likely will stay the same.

#### Supported Providers

For the first releases we'll be focusing on the major social networks and OpenId providers (because those are easy to implement).
While most of the providers listed below are already implemented, they still not confirmed to be working properly and may need
fixing and enhancement.

More information on how to implement IDPs into Hybridauth 3 can be found at 
http://hybridauth.github.io/developer-ref-extend-hybridauth.html

Provider      | Specs    | Implemented? | Working?    | Notes
------------- | -------- | ------------ |------------ | ------------------------------------------------------------------------------
Facebook      | OAuth2   |  [X]         |             | Authentication and getUserProfile are working. 
Twitter       | OAuth1   |  [X]         |             | Authentication and getUserProfile are working. 
Google        | OAuth2   |  [X]         |             | Authentication and getUserProfile are working.
Yahoo         | OAuth2   |              |             | Not implemented yet.
Windows Live  | OAuth2   |  [X]         |             |
LinkedIn      | OAuth2   |  [X]         |             | We no longer use OAauth1. Only authentication and getUserProfile are implemented.
Foursquare    | OAuth2   |  [X]         |             | 
Disqus        | OAuth2   |  [X]         |             | 
Dribbble      | OAuth2   |  [X]         |             | 
GitHub        | OAuth2   |  [X]         | [X]         | 
Instagram     | OAuth2   |  [X]         |             | 
Reddit        | OAuth2   |  [X]         | [X]         | 
WordPress     | OAuth2   |  [X]         | [X]         | 
Tumblr        | OAuth1   |  [X]         |             | 
TwitchTV      | OAuth2   |  [X]         |             | 
Px500         | OAuth1   |  [X]         |             | No clue how important this idp is, but was easy to port, so i did it.
Freeagent     | OAuth2   |  [X]         |             |     //
PixelPin      | OAuth2   |  [X]         |             |     //
Vkontakte     | OAuth2   |  [X]         |             | Russian idp.
Mailru        | OAuth2   |  [X]         |             |     //
Odnoklassniki | OAuth2   |  [X]         |             |     //
OpenID        | OpenID   |  [X]         | [X]         | Generic OpenID Adapter. Implemented and Working.
AOL           | OpenID   |  [X]         |             | Does the AOL still a thing? We may drop it otherwise.
PaypalOpenID  | OpenID   |  [X]         |             | Implemented but needs someone to confirm it's a working as expected.
Stackoverflow | OpenID   |  [X]         | [X]         | All good.
YahooOpenID   | OpenID   |  [X]         | [X]         | All good.
Steam         | Hybrid   |  [X]         | [X]         | Steam Adapter is a mix of OpenID and a Proprietary API.

-----------------------

### Optional Tasks:

The following tasks are not required in order to release Hybridauth 3 but they're nice to have.

[ ] **Peer review**

Hybridauth 3 is a complete rewrite of the current V2, and given the sensitive topic it deals with (i.e, users authentication),
it would be extremely valuable to have the entire code base examined and reviewed for safety and security purposes.

[ ] **Unit tests**

This one has been on the project's wishlist for as long as Hybridauth library existed. If you feel adventurous enough to take
on this task, then please give it a shot.

[ ] **Add even more providers**

As mentioned above, for the first release we'll focusing on the major providers, but If there's a social networks that you care for
and you wish it to be included sooner, then feel free to port it from the V2 repository.
