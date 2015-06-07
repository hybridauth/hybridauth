Hybridauth V3.1 TODO list
=========================

### Required:

[X] **Core**

Hybridauth core has been entirely implemented and while new modification may occur in the future, the design is most likely
will stay the same.

[ ] **OAuth 1/2 Providers**

For the first releases of Hybridauth V3, we'll focusing on the major providers (i.g, facebook, google, twitter, etc.)

The following IDPs are based on either OAuth 1 or 2 specifications. While most of them are already implemented to work with
Hybridauth V3, they still need some tweaking and testing.

More information on how to implement IDPs into Hybridauth V3 can be found at 
http://hybridauth.github.io/developer-ref-extend-hybridauth.html

Provider      | Implemented? | Working?    | Notes
------------- | ------------ |------------ | ------------------------------------------------------------------------------
Facebook      |  [X]         | [ ]         | 
Twitter       |  [X]         | [ ]         | 
Google        |  [X]         | [ ]         | 
Yahoo         |  [ ]         | [ ]         | Not implemented yet.
Windows Live  |  [ ]         | [ ]         | Not implemented yet.
LinkedIn      |  [ ]         | [ ]         | Not implemented yet.
Foursquare    |  [X]         | [ ]         | 
Disqus        |  [X]         | [ ]         | 
Dribbble      |  [X]         | [ ]         | 
GitHub        |  [X]         | [ ]         | 
Instagram     |  [X]         | [ ]         | 
Reddit        |  [X]         | [X]         | 
WordPress     |  [X]         | [X]         | 
Tumblr        |  [X]         | [ ]         | 
TwitchTV      |  [X]         | [ ]         | 
Vkontakte     |  [X]         | [ ]         |
Mailru        |  [X]         | [ ]         |
Odnoklassniki |  [X]         | [ ]         |


[ ] **OpenID Providers**

The following IDPs are based on OpenID specifications. 

Provider      | Implemented? | Working?    | Notes
------------- | ------------ |------------ | ------------------------------------------------------------------------------
OpenID        |  [X]         | [X]         | Generic OpenID Adapter. Implemented and Working.
AOL           |  [X]         | [ ]         | Does the AOL still a thing? We may drop it otherwise.
PaypalOpenID  |  [X]         | [ ]         | Implemented but needs someone to confirm it's a working as expected.
Stackoverflow |  [X]         | [X]         | All good.
YahooOpenID   |  [X]         | [X]         | All good.
Steam         |  [X]         | [X]         | Steam Adapter is a mix of OpenID and a Proprietary API.


### Optional:

The following tasks are not required in order to release Hybridauth 3 but they're nice to have.

[ ] **Peer review**

Hybridauth V3 is a complete rewrite of the current V2, and given the sensitive topic it deals with (i.e, users authentication),
it would be extremely valuable to have the entire code base examined and reviewed for safety and security purposes.

[ ] **Additional providers**

As mentioned above, for the first releases of Hybridauth 3, we'll focusing on the major providers. If there's a social networks
that you care for and you wish it to be included sooner, then feel free to port it from the V2 repository.

V2 additional IDPs can be found at: 

https://github.com/hybridauth/hybridauth/tree/master/additional-providers

Documentation on how to upgrade these providers to V3 can be found at:

http://hybridauth.github.io/developer-ref-extend-hybridauth.html

[ ] **Unit tests**

This one has been on the project's wishlist for as long as Hybridauth library existed. If you feel adventurous enough to take
on this task, then please give it a shot.
