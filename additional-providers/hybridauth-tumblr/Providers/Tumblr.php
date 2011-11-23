<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
* Hybrid_Providers_Tumblr 
*/
class Hybrid_Providers_Tumblr extends Hybrid_Provider_Model_OAuth1
{
   	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// provider api end-points
		$this->api->api_base_url      = "http://www.tumblr.com/";
		$this->api->authorize_url     = "http://www.tumblr.com/oauth/authorize";
		$this->api->request_token_url = "http://www.tumblr.com/oauth/request_token";
		$this->api->access_token_url  = "http://www.tumblr.com/oauth/access_token";
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'http://www.tumblr.com/api/authenticate' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		try{ 
			$response = @ new SimpleXMLElement( $response ); 

			// the easy way (well 4 me at least)
			$xml2array = @ $this->xml2array( $response );

			$this->user->profile->identifier    = @ (string) $xml2array["children"]["tumblelog"][0]["attr"]["url"]; 
			$this->user->profile->displayName  	= @ (string) $xml2array["children"]["tumblelog"][0]["attr"]["name"];
			$this->user->profile->profileURL 	= @ (string) $xml2array["children"]["tumblelog"][0]["attr"]["url"]; 
			$this->user->profile->webSiteURL 	= @ (string) $xml2array["children"]["tumblelog"][0]["attr"]["url"]; 
			$this->user->profile->photoURL   	= @ (string) $xml2array["children"]["tumblelog"][0]["attr"]["avatar-url"]; 
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
		}

		return $this->user->profile;
 	}

   	/**
	* load the current logged in user contacts list from the IDp api client  
	*/
	function getUserContacts() 
	{
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

   	/**
	* return the user activity stream  
	*/
	function getUserActivity( $stream ) 
	{
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

   	/**
	* return the user activity stream  
	*/ 
	function setUserStatus( $status )
	{
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

   /**
	* Utility function, convert xml to array
	*/
	public function xml2array($xml) { 
		$arXML=array(); 
		$arXML['name']=trim($xml->getName()); 
		$arXML['value']=trim((string)$xml); 
		$t=array(); 
		foreach($xml->attributes() as $name => $value){ 
			$t[$name]=trim($value); 
		} 
		$arXML['attr']=$t; 
		$t=array(); 
		foreach($xml->children() as $name => $xmlchild) { 
			$t[$name][]=$this->xml2array($xmlchild); //FIX : For multivalued node 
		} 
		$arXML['children']=$t; 
		return($arXML); 
	}
}
