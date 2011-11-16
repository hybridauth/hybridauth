<?php
/**
* Modified for HybridAuth by zachy
*/

/**
 *
 * FILE:        OAuthWrapHandler.php
 *
 * DESCRIPTION: Sample implementation of OAuth WRAP Authentication protocol.
 *
 * VERSION:     1.1
 *
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *
 *
 * OAuthWrapHandler
 * This handler is used to both process the raw HTTP requests that generate the
 * required cookies for the Windows Live javascript control to work within a
 * php applicaiton.
 */
class OAuthWrapHandler
{
    /**
     * Parses the http query information that is present in the request to this
     * page and seperates out http params into the cookies that the JavaScript API
     * expects it then requests an access token using the verification code that was
     * returned.
     */
    public function ProcessRequest()
    {
        //Clear existing cookies
        $this->ExpireCookies();
        //Parse reaponse from authentication request
        $cookies_setup = $this->convertParamsToCookies($_REQUEST);
        //Get access token
        if(isset($cookies_setup['verification_code']))
        {
            //request the access token
            $auth_params = $this->getAuthorizationToken(
                    WRAP_ACCESS_URL
                    , WRAP_CLIENT_ID
                    , WRAP_CLIENT_SECRET
                    , WRAP_CALLBACK
                    , $cookies_setup['verification_code']
            );
            
            //remove the code from the output cookies so the users
            //don't know what the gatekey is.
            unset($cookies_setup['verification_code']);
        }
        else
        {
            throw new Exception("No verification Code returned from Windows Live Services.");
        }

        $cookies_auth = $this->convertParamsToCookies($auth_params);
        $cookies = array_merge($cookies_setup, $cookies_auth);
        $this->setAuthCookies($cookies);
		
		return $cookies;
    }

    /**
     * Removes any of the existing cookies that may have been sent for the
     * JavaScript API setting each of the cookies to be expired yesterday.
     */
    public function ExpireCookies()
    {
        setcookie ("c_accessToken", "", time() - 3600);
        setcookie ("c_clientId", "", time() - 3600);
        setcookie ("c_clientState", "", time() - 3600);
        setcookie ("c_scope", "", time() - 3600);
        setcookie ("c_error", "", time() - 3600);
        setcookie ("c_uid", "", time() - 3600);
        setcookie ("c_expiry", "", time() - 3600);
        setcookie ("lca", "", time() - 3600);
    }

    /**
     * Resgister and send to the HTTP header stream the cookies that the
     * JavaScript API requires in order to function.
     *
     * @param array $cookies An indexed array containing the cookies that are to be sent to the browser.
     */
    private function setAuthCookies($cookies)
    {
        foreach($cookies as $key => $value)
        {
            setcookie ($key, $value, time() + 36000);
        }
        setcookie ('c_clientId', WRAP_CLIENT_ID, time() + 36000); //clientID == appId
        setcookie ('lca', 'done', time() + 36000); //lca //done
    }

    /**
     * Parses the contents of an array, seperating out the fields that are associated
     * with WRAP authentication and moves these fields into an indexed array.
     *
     * @param array $array The array that contains the HTTP params that need to be parsed. Typically $_REQUEST or the results of a manualy cURL post request.
     * @return array An Array of the cookies that can be sent to the browser.
     */
    private function convertParamsToCookies($array)
    {
        $cookies = array();

        foreach(array_keys($array) as $getParam)
        {
            $getParam = urldecode($getParam);
            switch($getParam)
            {
                case 'wrap_client_state':
                    //if(strrpos($array['wrap_client_state'], 'js_close_window') >= 0)
                    $cookies['c_clientState'] = $array['wrap_client_state'];
                    break;
                case 'wrap_verification_code':
                    $cookies['verification_code'] = $array['wrap_verification_code'];
                    break;
                case 'exp': //scope
                    $cookies['c_scope'] = str_replace(';', ',',$array['exp']);
                    break;
                case 'error_code':
                    $cookies['c_error'] = ' ' . $array['error_code'];
                    break;
                case 'wrap_error_reason':
                    $cookies['c_error'] = ' ' . $array['wrap_error_reason'];
                    break;
                case 'wrap_access_token':
                    $cookies['c_accessToken']= $array['wrap_access_token'];
                    break;
                case 'wrap_access_token_expires_in':
                    $cookies['c_expiry']= date('j/m/Y g:i:s A', $array['wrap_access_token_expires_in']);
                    break;
                case 'uid':
                    $cookies['c_uid']= $array['uid'];
                    break;
            }
        }
        return $cookies;
    }

    /**
     * Issues an syncronous authorization request by generating a https POST request
     * to the LIVE authentication servers. The result, whether successful or unsuccessful
     * is returned to the calling function, parsed and displayed to the user and saved
     * to the session.
     *
     * @param string $authUrl The URL to the LIVE authorisation service where you request authorisation.
     * @param string $appId The LIVE id of the applicaiton that identifies it to the LIVE services.
     * @param string $appSecrect The secret applicaiton key that paris with the applicaiton id.
     * @param string $callbackUrl A url that is pass as a callback, it is not used and is not check by LIVE it is cosmetic.
     * @param string $verificationCode The verification code that was returned as part of the consent request, which has an expiry.
     */
    private function getAuthorizationToken($authUrl, $appId, $appSecret, $callbackUrl, $verificationCode)
    {
        //Using the returned verification code build a query to the
        //authorization url that will return the authorized token.

        $tokenRequest = 'wrap_client_id=' . urlencode($appId)
                . '&wrap_client_secret=' . urlencode($appSecret)
                . '&wrap_callback=' . urlencode($callbackUrl)
                . '&wrap_verification_code=' . urlencode($verificationCode);
        $response = $this->postWRAPRequest($authUrl, $tokenRequest);
        return $this->parseWRAPResponse($response);
    }

    /**
     * Issues a syncronous http POST request to a url and returns the response
     * headers as well as the response content in a single string.
     *
     * @param string $posturl The web url that the POST request is to be issued to
     * @param string $postvars The post variables that you are issuing as part
     *      of the POST request. They must be in the format var1=val1&var2=va12.
     *      Note that there is no leading '?' and also not that the individual values
     *      need to be urlencoded but the $postvars string itself must not be.
     * @return string A Url decoded string that contains the information returned from the HTTP/HTTPS request.
     */
    private function postWRAPRequest($posturl, $postvars)
    {
        $ch = curl_init($posturl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
        @ curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $Rec_Data = curl_exec($ch);
        curl_close($ch);

        return urldecode($Rec_Data);
    }

    /**
     * Extract from the POST response any returned variables whether they be error
     * values or expected values.
     *
     * @param string $response The HTTP response string that contains the header and html string to format.
     * @return array Array containing parsed HTTP parameters in hte format array[param] = 'value'
     */
    private function parseWRAPResponse($response)
    {
        //Firslty remove any extraneous header information from the returned POST variables
        $pos = strpos($response, 'wrap_access_token=');
        if ($pos === false)
        {
            $pos = strpos($response, 'wrap_error_reason=');
        }
        $codes = '?' . substr($response, $pos, strlen($response));

        //RegEx the string to seperate out the variables and thier values
        if (preg_match_all('/[?&]([^&=]+)=([^&=]+)/', $codes, $matches))
        {
            for($i =0; $i < count($matches[1]); $i++)
            {
                //The first element in the matches array is the combination
                //of both matches.
                $contents[$matches[1][$i]] = $matches[2][$i];
            }
        }
        else
        {
            throw new Exception('No matches for regular expression.');
        }
        return $contents;
    }
	
	
	/**
	 * GET
	 * Performs a cUrl request with a url generated by MakeUrl. The useragent of the request is hardcoded
	 * as the Google Chrome Browser agent
	 * @param String $url The base url to query
	 * @param Array $params The parameters to pass to the request
	 * @param boolean $usecurl Default:true, whether or not to perform the request using cUrl
	 */
	public function GET($url,$params=false,$auth=false){
		
		$url = $this->MakeUrl($url,$params);
		// borrowed from Andy Langton: http://andylangton.co.uk/
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		
		curl_setopt($ch,CURLOPT_HTTPHEADER,array (
			"Authorization: WRAP access_token=$auth",
			"Content-Type: application/json",
			"Accept: application/json"
		));
		
		if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		}else {
			// Handle the useragent like we are Google Chrome
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.X.Y.Z Safari/525.13.');
		}
		curl_setopt($ch , CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result=curl_exec($ch);
		$info=curl_getinfo($ch);
		curl_close($ch);
		
		return $result;
	}
		
	/**
	 * MakeUrl
	 * Takes a base url and an array of parameters and sanitizes the data, then creates a complete
	 * url with each parameter as a GET parameter in the URL
	 * @param String $url The base URL to append the query string to (without any query data)
	 * @param Array $params The parameters to pass to the URL
	 */	
	public function MakeUrl($url,$params){
		if(!empty($params) && $params){
			foreach($params as $k=>$v) $kv[] = "$k=$v";
			$url_params = str_replace(" ","+",implode('&',$kv));
			$url = trim($url) . '?' . $url_params;
		}
		return $url;
	}
}
