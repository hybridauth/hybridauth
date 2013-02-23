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
		curl_setopt( $ch, CURLOPT_TIMEOUT        , 30 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
		curl_setopt( $ch, CURLOPT_USERAGENT      , "HybridAuth Client http://hybridauth.sourceforge.net/" );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER , false ); // fixme!!

		if( isset( $request->headers ) && is_array( $request->headers ) && $request->headers ){
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->headers );
		}

		if( $this->method == 'POST' ){
			curl_setopt($ch, CURLOPT_POST, 1);

			if( $this->params ){
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->params );
			}
		}

		if( isset( $request->curlOptions ) && is_array( $request->curlOptions ) && $request->curlOptions ){
			foreach( $request->curlOptions as $opt => $val ){
				if( $val !== null ){
					curl_setopt( $ch, $opt, $val );
				}
			}
		}

		$response->body = curl_exec($ch);

		$response->statusCode   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$response->curlHttpInfo = curl_getinfo( $ch );

		curl_close ($ch);

		return $response; 
	}
}
