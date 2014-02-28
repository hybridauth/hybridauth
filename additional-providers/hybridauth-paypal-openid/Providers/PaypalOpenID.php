<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_PayPal class 
 */
class Hybrid_Providers_PaypalOpenID extends Hybrid_Provider_Model_OpenID
{
    var $openidIdentifier = "https://www.sandbox.paypal.com/webapps/auth/server"; 

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		if( empty( $this->openidIdentifier ) ){
			throw new Exception( "OpenID adapter require the identity provider identifier 'openid_identifier' as an extra parameter.", 4 );
		}

		$this->api->identity  = $this->openidIdentifier;
		$this->api->returnUrl = $this->endpoint;
		$this->api->required  = ARRAY(
			/*'namePerson/first'       ,
			'namePerson/last'        ,
			'namePerson/friendly'    ,
			'namePerson'             ,

			'contact/email'          ,

			'birthDate'              ,
			'birthDate/birthDay'     ,
			'birthDate/birthMonth'   ,
			'birthDate/birthYear'    ,

			'person/gender'          ,
			'pref/language'          , 

			'contact/postalCode/home',
			'contact/city/home'      ,
			'contact/country/home'   , 

            'media/image/default'    ,*/

            'namePerson/prefix',
            'namePerson/first',
            'namePerson/last',
            'namePerson/middle',
            'namePerson/suffix',
            'namePerson/friendly',
            'person/guid',
            'birthDate/birthYear',
            'birthDate/birthMonth',
            'birthDate/birthday',
            'gender',
            'language/pref',
            'contact/phone/default',
            'contact/phone/home',
            'contact/phone/business',
            'contact/phone/cell',
            'contact/phone/fax',
            'contact/postaladdress/home',
            'contact/postaladdressadditional/home',
            'contact/city/home',
            'contact/state/home',
            'contact/country/home',
            'contact/postalcode/home',
            'contact/postaladdress/business',
            'contact/postaladdressadditional/business',
            'contact/city/business',
            'contact/state/business',
            'contact/country/business',
            'contact/postalcode/business',
            /*'contact/IM/default',
            'contact/IM/AIM',
            'contact/IM/ICQ',
            'contact/IM/MSN',
            'contact/IM/Yahoo',
            'contact/IM/Jabber',
            'contact/IM/Skype',
            'contact/internet/email',
            'contact/web/default',
            'contact/web/blog',
            'contact/web/Linkedin',
            'contact/web/Amazon',
            'contact/web/Flickr',
            'contact/web/Delicious',*/
            'company/name',
            'company/title',
            /*'media/spokenname',
            'media/greeting/audio',
            'media/greeting/video',
            'media/biography',
            'media/image',
            'media/image/16x16',
            'media/image/32x32',
            'media/image/48x48',
            'media/image/64x64',
            'media/image/80x80',
            'media/image/128x128',
            'media/image/160x120',
            'media/image/320x240',
            'media/image/640x480',
            'media/image/120x160',
            'media/image/240x320',
            'media/image/480x640',
            'media/image/favicon',
            'timezone',*/
		);
		$this->api->optional  = array();ARRAY( 
            'namePerson/prefix',
            'namePerson/first',
            'namePerson/last',
            'namePerson/middle',
            'namePerson/suffix',
            'namePerson/friendly',
            'person/guid',
            'birthDate/birthYear',
            'birthDate/birthMonth',
            'birthDate/birthday',
            'gender',
            'language/pref',
            'contact/phone/default',
            'contact/phone/home',
            'contact/phone/business',
            'contact/phone/cell',
            'contact/phone/fax',
            'contact/postaladdress/home',
            'contact/postaladdressadditional/home',
            'contact/city/home',
            'contact/state/home',
            'contact/country/home',
            'contact/postalcode/home',
            'contact/postaladdress/business',
            'contact/postaladdressadditional/business',
            'contact/city/business',
            'contact/state/business',
            'contact/country/business',
            'contact/postalcode/business',
            /*'contact/IM/default',
            'contact/IM/AIM',
            'contact/IM/ICQ',
            'contact/IM/MSN',
            'contact/IM/Yahoo',
            'contact/IM/Jabber',
            'contact/IM/Skype',
            'contact/internet/email',
            'contact/web/default',
            'contact/web/blog',
            'contact/web/Linkedin',
            'contact/web/Amazon',
            'contact/web/Flickr',
            'contact/web/Delicious',*/
            'company/name',
            'company/title',
            /*'media/spokenname',
            'media/greeting/audio',
            'media/greeting/video',
            'media/biography',
            'media/image',
            'media/image/16x16',
            'media/image/32x32',
            'media/image/48x48',
            'media/image/64x64',
            'media/image/80x80',
            'media/image/128x128',
            'media/image/160x120',
            'media/image/320x240',
            'media/image/640x480',
            'media/image/120x160',
            'media/image/240x320',
            'media/image/480x640',
            'media/image/favicon',
            'timezone',*/
        );

		# redirect the user to the provider authentication url
		Hybrid_Auth::redirect( $this->api->authUrl() );
	}
}

