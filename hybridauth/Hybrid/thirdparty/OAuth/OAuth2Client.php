<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// A service client for the OAuth 2 flow.
// v0.1.1
class OAuth2Client
{
  public $api_base_url     = "";
  public $authorize_url    = "";
  public $token_url        = "";
  public $token_info_url   = "";

  public $client_id        = "" ;
  public $client_secret    = "" ;
  public $redirect_uri     = "" ;
  public $access_token     = "" ;
  public $refresh_token    = "" ;

  public $access_token_expires_in = "" ;
  public $access_token_expires_at = "" ;

  //--

  public $sign_token_name          = "access_token";
  public $curl_time_out            = 30;
  public $curl_connect_time_out    = 30;
  public $curl_ssl_verifypeer      = false;
  public $curl_ssl_verifyhost      = false;
  public $curl_header              = array();
  public $curl_useragent           = "OAuth/2 Simple PHP Client v0.1.1; HybridAuth http://hybridauth.sourceforge.net/";
  public $curl_authenticate_method = "POST";
  public $curl_proxy               = null;
  public $curl_compressed          = false;
  //--

  public $http_code             = "";
  public $http_info             = "";
  protected $response           = null;

  //--

  public function __construct( $client_id = false, $client_secret = false, $redirect_uri='', $compressed = false )
  {
    $this->client_id       = $client_id;
    $this->client_secret   = $client_secret;
    $this->redirect_uri    = $redirect_uri;
    $this->curl_compressed = $compressed;
  }

  public function authorizeUrl( $extras = array() )
  {
    $params = array(
      "client_id"     => $this->client_id,
      "redirect_uri"  => $this->redirect_uri,
      "response_type" => "code"
    );

    if( count($extras) )
      foreach( $extras as $k=>$v )
        $params[$k] = $v;

    return $this->authorize_url . "?" . http_build_query($params, '', '&');
  }

  public function authenticate( $code )
  {
    $params = array(
      "client_id"     => $this->client_id,
      "client_secret" => $this->client_secret,
      "grant_type"    => "authorization_code",
      "redirect_uri"  => $this->redirect_uri,
      "code"          => $code
    );

    $response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );

    $response = $this->parseRequestResult( $response );

    if( ! $response || ! isset( $response->access_token ) ){
      throw new Exception( "The Authorization Service has return: " . $response->error );
    }

    if( isset( $response->access_token  ) )  $this->access_token           = $response->access_token;
    if( isset( $response->refresh_token ) ) $this->refresh_token           = $response->refresh_token;
    if( isset( $response->expires_in    ) ) $this->access_token_expires_in = $response->expires_in;

    // calculate when the access token expire
    if( isset($response->expires_in)) {
      $this->access_token_expires_at = time() + $response->expires_in;
    }

    return $response;
  }

  public function authenticated()
  {
    if ( $this->access_token ){
      if ( $this->token_info_url && $this->refresh_token ){
        // check if this access token has expired,
        $tokeninfo = $this->tokenInfo( $this->access_token );

        // if yes, access_token has expired, then ask for a new one
        if( $tokeninfo && isset( $tokeninfo->error ) ){
          $response = $this->refreshToken( $this->refresh_token );

          // if wrong response
          if( ! isset( $response->access_token ) || ! $response->access_token ){
            throw new Exception( "The Authorization Service has return an invalid response while requesting a new access token. given up!" );
          }

          // set new access_token
          $this->access_token = $response->access_token;
        }
      }

      return true;
    }

    return false;
  }

  /**
  * Format and sign an oauth for provider api
  */
  public function api( $url, $method = "GET", $parameters = array(), $decode_json = true )
  {
    if ( strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0 ) {
      $url = $this->api_base_url . $url;
    }


    // Add access_token only if it's not available in curl headers.
    $auth_header = array_filter($this->curl_header, function ($header) {
        return strpos($header, 'Authorization:') === 0;
    });
    if (!$auth_header) {
        $parameters[$this->sign_token_name] = $this->access_token;
    }

    $response = null;
    switch( $method ){
      case 'GET'  : $response = $this->request( $url, $parameters, "GET"  ); break;
      case 'POST' : $response = $this->request( $url, $parameters, "POST" ); break;
      case 'DELETE' : $response = $this->request( $url, $parameters, "DELETE" ); break;
      case 'PATCH'  : $response = $this->request( $url, $parameters, "PATCH" ); break;
    }

    if( $response && $decode_json ){
      return $this->response = json_decode( $response );
    }

    return $this->response = $response;
  }

  /**
   * Return the response object afer the fact
   *
   * @return mixed
   */
  public function getResponse()
  {
      return $this->response;
  }

  /**
  * GET wrapper for provider apis request
  */
  function get( $url, $parameters = array(), $decode_json = true )
  {
    return $this->api( $url, 'GET', $parameters, $decode_json );
  }

  /**
  * POST wrapper for provider apis request
  */
  function post( $url, $parameters = array(), $decode_json = true )
  {
    return $this->api( $url, 'POST', $parameters, $decode_json );
  }

  // -- tokens

  public function tokenInfo($accesstoken)
  {
    $params['access_token'] = $this->access_token;
    $response = $this->request( $this->token_info_url, $params );
    return $this->parseRequestResult( $response );
  }

  public function refreshToken( $parameters = array() )
  {
    $params = array(
      "client_id"     => $this->client_id,
      "client_secret" => $this->client_secret,
      "grant_type"    => "refresh_token"
    );

    foreach($parameters as $k=>$v ){
      $params[$k] = $v;
    }

    $response = $this->request( $this->token_url, $params, "POST" );
    return $this->parseRequestResult( $response );
  }

  // -- utilities

  private function request( $url, $params=false, $type="GET" )
  {
    Hybrid_Logger::info( "Enter OAuth2Client::request( $url )" );
    Hybrid_Logger::debug( "OAuth2Client::request(). dump request params: ", serialize( $params ) );

	$urlEncodedParams = http_build_query($params, '', '&');

    if( $type == "GET" ){
      $url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . $urlEncodedParams;
    }

    $this->http_info = array();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL            , $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
    curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
    curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , $this->curl_ssl_verifyhost );
    curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->curl_header );

    if ($this->curl_compressed){
      curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
    }

    if($this->curl_proxy){
      curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
    }

    if ($type == "POST") {
      curl_setopt($ch, CURLOPT_POST, 1);

      // If request body exists then encode it for "application/json".
      if (isset($params['body'])) {
        $urlEncodedParams = json_encode($params['body']);
      }

      // Using URL encoded params here instead of a more convenient array
      // cURL will set a wrong HTTP Content-Type header if using an array (cf. http://www.php.net/manual/en/function.curl-setopt.php, Notes section for "CURLOPT_POSTFIELDS")
      // OAuth requires application/x-www-form-urlencoded Content-Type (cf. https://tools.ietf.org/html/rfc6749#section-2.3.1)
      if ($params) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $urlEncodedParams);
      }
    }

    if( $type == "DELETE" ){
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    if( $type == "PATCH" ){
      curl_setopt($ch, CURLOPT_POST, 1);
      if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    }
    $response = curl_exec($ch);
    if( $response === false ) {
        Hybrid_Logger::error( "OAuth2Client::request(). curl_exec error: ", curl_error($ch) );
    }
    Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( curl_getinfo($ch) ) );
    Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", serialize( $response ) );

    $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->http_info = array_merge($this->http_info, curl_getinfo($ch));

    curl_close ($ch);

    return $response;
  }

  private function parseRequestResult( $result )
  {
    if( json_decode( $result ) ) return json_decode( $result );

    parse_str( $result, $output );

    $result = new StdClass();

    foreach( $output as $k => $v )
      $result->$k = $v;

    return $result;
  }
  /**
 * DELETE wrapper for provider apis request
 */
 function delete( $url, $parameters = array() )
 {
   return $this->api( $url, 'DELETE', $parameters );
 }
 /**
 * PATCH wrapper for provider apis request
 */
 function patch( $url, $parameters = array() )
 {
    return $this->api( $url, 'PATCH', $parameters );
 }
}
