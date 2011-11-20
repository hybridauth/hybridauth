<?php
class googleoauth
{
	public $authurl      = "https://accounts.google.com/o/oauth2/auth";  
	public $tokenurl     = "https://accounts.google.com/o/oauth2/token"; 
	public $tokeninfourl = "https://www.googleapis.com/oauth2/v1/tokeninfo"; 

	public $clientid; 
	public $clientsecret; 
    public $redirecturi;  
	public $useragent    = "GoogleOAuth/2 Simple PHP Client v0.1 https://github.com/hybridauth/googleoauth";
	public $sessionkey   = "" ;
	public $accesstoken  = "" ; 
	public $refreshtoken = "" ;

	public function __construct( $client_id = false, $client_secret = false, $redirect_uri='' )
	{
		$this->clientid     = $client_id;
		$this->clientsecret = $client_secret; 
		$this->redirecturi  = $redirect_uri;

		$this->sessionkey   = "googleoauth_session"; 

		if ( isset( $_SESSION[$this->sessionkey]["access_token"] ) ){
			$this->accesstoken  = $_SESSION[$this->sessionkey]["access_token"];
			$this->refreshtoken = $_SESSION[$this->sessionkey]["refresh_token"];
		}
	}

	public function authenticated()
	{
		if ( $this->accesstoken ){
			// check if this access token has expired, 
			$tokeninfo = $this->tokeninfo( $this->accesstoken );

			// if yes, access_token has expired, then ask for a new one
			if( $tokeninfo && isset( $tokeninfo->error ) ){
				$response = $this->refreshtoken( $this->refreshtoken );

				// if wrong response
				if( ! isset( $response->access_token ) || ! $response->access_token ){
					throw new Exception( "The Google Authorization Server has return an invalid response while requesting a new access token. given up!" ); 
				}

				// set new access_token
				$this->accesstoken = $_SESSION[$this->sessionkey]["access_token"] = $response->access_token; 
			}
			// else, the access_token still valid, then set the one we already have on session
			else{ 
				$this->accesstoken = $_SESSION[$this->sessionkey]["access_token"]; 
			}

			return true;
		}

		return false;
	}

	public function tokeninfo($accesstoken)
	{
		$params['access_token'] = $this->accesstoken;
		$result = $this->request( $this->tokeninfourl, $params ); 
		return json_decode($result);  
	}

	public function refreshtoken($refreshtoken)
	{
		$params = array( 
			"client_id" => $this->clientid, 
			"client_secret" => $this->clientsecret, 
			"refresh_token" => $refreshtoken,
			"grant_type" => "refresh_token"
		); 

		$result = $this->request( $this->tokenurl, $params, "POST" ); 

		return json_decode($result);  
	}

	public function loginurl( $scope = "https://www.googleapis.com/auth/userinfo.profile" )
	{
		$params = array( 
			"client_id"      => $this->clientid, 
			"response_type"  => "code", 
			"access_type"    => "offline" ,
			"scope"          => $scope, 
			"redirect_uri"   => $this->redirecturi 
		);

		return $this->authurl . "?" . http_build_query( $params );
	}

    public function authenticate( $auto = true )
	{
        if( isset( $_REQUEST["code"] ) ){
			$params = array(
				"client_id"     => $this->clientid,
				"client_secret" => $this->clientsecret,
				"grant_type"    => "authorization_code",
				"redirect_uri"  => $this->redirecturi,
				"code"          => $_REQUEST["code"]
			);

			$result = $this->request( $this->tokenurl, $params, "POST" );  
			$authorization = json_decode($result); 

			if( $authorization && isset( $authorization->access_token ) ){ 
				// sotre tokens
				$_SESSION[$this->sessionkey]["access_token"]  = $this->accesstoken  = $authorization->access_token; 
				$_SESSION[$this->sessionkey]["refresh_token"] = $this->refreshtoken = $authorization->refresh_token; 

				$_SESSION[$this->sessionkey]["token_type"]    = $authorization->token_type; 
				$_SESSION[$this->sessionkey]["id_token"]      = $authorization->id_token;  
			}
			else{
				throw new Exception( "The Google Authorization Server has return an error: " . $authorization->error );
			}

			if( $auto ){ 
				header('Location: ' . $this->redirecturi );

				die();
			}

			return $authorization; 
        }
		// if google return an error
		elseif( isset( $_REQUEST["error"] ) && $auto ){ 
			throw new Exception( "The Google Authorization Server has return an error: " . strip_tags( $_REQUEST["error"] ) );
		}
    }

	public function disconnect()
	{
        unset( $_SESSION[$this->sessionkey] );

		$this->accesstoken = "";
		$this->refreshtoken = ""; 
	}

	public function call( $url, $params=array(), $POST=false )
	{
		$params['access_token'] = $this->accesstoken;
		if(!$POST) return $this->request( $url, $params ); 
		else return $this->request( $url, $params, "POST" ); 
	}

	public function request( $url, $params=false, $type="GET" )
	{
		if( $type == "GET" ){
			$url = $url . "?" . http_build_query( $params );
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent );
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		if( $type == "POST" ){
			curl_setopt($ch, CURLOPT_POST, 1); 
			if($params) curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		$result=curl_exec($ch);
		$info=curl_getinfo($ch);
		curl_close($ch);

		// echo "<hr/>";
		// echo "<pre>";
		// echo "url:$type=$url\n"; 
		// print_r( $params );
		// print_r( $result );
		// echo "</pre>";
		// echo "<hr/>";

		return $result;
	} 
}
