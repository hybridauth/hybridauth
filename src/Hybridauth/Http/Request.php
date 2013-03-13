<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

use Hybridauth\Http\Response;

class Request
{
	protected $curlOptions = null;

	// --------------------------------------------------------------------

	function send( $uri, $method, $args, $headers = array(), $body = null )
	{
		if( empty( $uri ) ){
			return false;
		}

		$response = new Response();

		if( $method == 'GET' ){
			$uri = $uri . ( strpos( $uri, '?' ) ? '&' : '?' ) . http_build_query( $args );
		}

		if( $method == 'POST' && ! isset( $headers['Content-type'] ) ) {
			$headers['Content-type'] = 'Content-type: application/x-www-form-urlencoded';
		}

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL            , $uri );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER ,    1 );

		$curl_opts = array(
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => 0, // its your call now 
			CURLOPT_USERAGENT      => "HybridAuth Library http://hybridauth.sourceforge.net/",
		);

		if( $this->curlOptions ){
			foreach( $this->curlOptions as $opt => $val ){
				$curl_opts[ $opt ] = $val;
			}
		}

		foreach( $curl_opts as $opt => $val ){
			curl_setopt( $ch, $opt, $val );

			$this->curlOptions[ $opt ] = $val;
		}

		if( isset( $headers ) && $headers ){
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		}

		if( $method == 'POST' ){
			curl_setopt( $ch, CURLOPT_POST, 1);

			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $args ) );
		}

		$response->setBody       ( curl_exec( $ch ) );
		$response->setStatusCode ( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) );
		$response->setErrorCode  ( curl_errno( $ch ) ); // http://curl.haxx.se/libcurl/c/libcurl-errors.html

		$response->setCurlHttpInfo( curl_getinfo( $ch ) );

		curl_close ($ch);

		return $response; 
	}

	// --------------------------------------------------------------------

	function setCurlOptions( $curlOptions )
	{
		$this->curlOptions = $curlOptions;
	}
}
