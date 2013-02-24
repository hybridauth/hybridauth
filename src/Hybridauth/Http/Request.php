<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

class Request
{
	public function send()
	{
		$response = new \Hybridauth\Http\Response();

		if( $this->method == 'GET' ){
			$this->uri = $this->uri . ( strpos( $this->uri, '?' ) ? '&' : '?' ) . http_build_query( $this->params );
		}

		$response->curlHttpInfo = array();

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL            , $this->uri );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER ,  1 );

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

		if( isset( $this->headers ) && is_array( $this->headers ) && $this->headers ){
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->headers );
		}

		if( $this->method == 'POST' ){
			curl_setopt($ch, CURLOPT_POST, 1);

			if( $this->params ){
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->params );
			}
		}

		$response->body = curl_exec($ch);

		$response->statusCode   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$response->errorCode    = curl_errno($ch); // http://curl.haxx.se/libcurl/c/libcurl-errors.html
		$response->curlHttpInfo = curl_getinfo( $ch );

		curl_close ($ch);

		return $response; 
	}
}
