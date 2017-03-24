Hybridauth 3.0 Progression
==========================

#### Core Components

Hybridauth core components are fully implemented, and while new modification may occur in near future, the design is most likely will be kepy the same.

Provider                | Implemented  | Working     | Notes
----------------------- | ------------ | ----------- | ------------------------------------------------------------------------------
Storage                 |  [X]         |  [X]        | Only PHP Session is implemented.
Logging                 |  [X]         |  [X]        | We provide a housemade logging using fles, and a wrapper for ps3 loggers for those who wish to use it.
Http Client             |  [X]         |  [X]        | We provide a default Client using Curl, and a wrapper for Guzzle for those who wish to use it.
User Entities           |  [X]         |  [X]        | User profile, contact and activity.
OAuth 1 Model           |  [X]         |  [X]        | 
OAuth 2 Model           |  [X]         |  [X]        | 
OpenID 1 Model          |  [X]         |  [X]        | 

#### Providers

Priority is given to major social networks and OpenId providers (because those are easy to implement). 

More information on how to implement IDPs into Hybridauth 3 can be found at https://hybridauth.github.io/developer-ref-extend-hybridauth.html

Provider      | Specs    | Implemented  | Working     | Notes
------------- | -------- | ------------ | ----------- | ------------------------------------------------------------------------------
Facebook      | OAuth2   |  [X]         |             | Only authentication and getUserProfile are implemented.
Twitter       | OAuth1   |  [X]         |  [X]        | 
Google        | OAuth2   |  [X]         |  [X]        | 
GitHub        | OAuth2   |  [X]         |  [X]        | 
Reddit        | OAuth2   |  [X]         |  [X]        | 
BitBucket     | OAuth2   |  [X]         |  [X]        | 
WordPress     | OAuth2   |  [X]         |             | 
Tumblr        | OAuth1   |  [X]         |             | 
Disqus        | OAuth2   |  [X]         |             | 
Dribbble      | OAuth2   |  [X]         |             | 
Windows Live  | OAuth2   |  [X]         |             | 
Foursquare    | OAuth2   |  [X]         |             | 
Instagram     | OAuth2   |  [X]         |             | 
LinkedIn      | OAuth2   |  [X]         |             | Only authentication and getUserProfile are implemented.
Yahoo         | OAuth2   |              |             | 
Vkontakte     | OAuth2   |  [X]         |             | Russian Provider and probably won't be able to test if working.
Mailru        | OAuth2   |  [X]         |             | Russian Provider and probably won't be able to test if working.
Odnoklassniki | OAuth2   |  [X]         |             | Russian Provider and probably won't be able to test if working.
OpenID        | OpenID   |  [X]         |  [X]        | Generic OpenID Adapter.
PaypalOpenID  | OpenID   |  [X]         |  [X]        | Uses OpenID.
Stackoverflow | OpenID   |  [X]         |  [X]        | Uses OpenID.
YahooOpenID   | OpenID   |  [X]         |  [X]        | Uses OpenID as name implies.
AOLOpenID     | OpenID   |  [X]         |  [X]        | Uses OpenID as name implies.
Steam         | Hybrid   |  [X]         |  [X]        | Steam Adapter is a mix of OpenID and a Proprietary API.
Discord       | Hybrid   |  [X]         |  [X]        |
TwitchTV      | OAuth2   |  [X]         |             | 

-----------------------

#### Optional Tasks:

The following tasks are not required in order to release Hybridauth 3 but they're nice to have.

[ ] **Peer review**

Hybridauth 3 is a complete rewrite of the current V2, and given the sensitive topic it deals with (i.e, users authentication), it would be extremely valuable to have the entire code base examined and reviewed for safety and security purposes.

[ ] **Unit tests**

This one has been on the project's wishlist for as long as Hybridauth library existed. If you feel adventurous enough to take on this task, then please give it a shot.

[ ] **Add even more providers**

As mentioned above, for the first release we'll focusing on the major providers, but If there's a social networks that you care and you wish it to be included sooner, then feel free to port it from the V2 repository.
