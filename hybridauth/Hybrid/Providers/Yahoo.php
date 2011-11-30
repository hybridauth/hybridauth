<?php
//!! planned to be replaced Y! openid by the oauth1 adapter on 2.0.10

/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
* Hybrid_Providers_Yahoo provider adapter based on OAuth1 protocol
*
* Provided as a way to keep backward compatibility for Yahoo OpenID based on HybridAuth <= 2.0.8 
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Yahoo.html
*/
class Hybrid_Providers_Yahoo extends Hybrid_Provider_Model_OpenID
{
	var $openidIdentifier = "https://www.yahoo.com"; 
}

