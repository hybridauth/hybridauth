<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Data;

/**
 * Parser
 *
 * This class is used to parse plain text into objects. It's used by hybriauth adapters to converts
 * providers api responses to a more 'manageable' format.
 */
final class Parser 
{
	/**
	* Decodes a string into an object. 
	*
	* This method will first attempt to parse data as a JSON string (since most providers use this format)
	* then parse_str.
	*
	* @param $result
	*
	* @return mixed
	*/
	function parse( $raw = null )
	{
		$data = $this->parseJson( $raw );

		if( ! $data )
		{
			$data = $this->parseQueryString( $raw );
		}

		return $data;
	}

	/**
	* Decodes a JSON string
	*
	* @param $result
	*
	* @return mixed
	*/
	function parseJson( $result )
	{
		return json_decode( $result );
	}

	/**
	* Parses a string into variables
	*
	* @param $result
	*
	* @return StdClass
	*/
	function parseQueryString( $result )
	{
		parse_str( $result, $output );

		if( ! is_array( $output ) )
		{
			return $result;
		}

		$result = new \StdClass();

		foreach( $output as $k => $v )
		{
			$result->$k = $v;
		}

		return $result;
	}
}
