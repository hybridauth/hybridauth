<?php
// OAuthWrapHandler.php 1.1 

/** 
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
    public function ProcessRequest()
    {
        $this->ExpireCookies();
        $cookies_setup = $this->convertParamsToCookies($_REQUEST);
        if(isset($cookies_setup['verification_code']))
        { 
            $auth_params = $this->getAuthorizationToken(
                    WRAP_ACCESS_URL
                    , WRAP_CLIENT_ID
                    , WRAP_CLIENT_SECRET
                    , WRAP_CALLBACK
                    , $cookies_setup['verification_code']
            );
 
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
 
    private function setAuthCookies($cookies)
    {
        foreach($cookies as $key => $value)
        {
            setcookie ($key, $value, time() + 36000);
        }
        setcookie ('c_clientId', WRAP_CLIENT_ID, time() + 36000); //clientID == appId
        setcookie ('lca', 'done', time() + 36000); //lca //done
    }
 
    private function convertParamsToCookies($array)
    {
        $cookies = array();

        foreach(array_keys($array) as $getParam)
        {
            $getParam = urldecode($getParam);
            switch($getParam)
            {
                case 'wrap_client_state': 
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
 
    private function getAuthorizationToken($authUrl, $appId, $appSecret, $callbackUrl, $verificationCode)
    { 
        $tokenRequest = 'wrap_client_id=' . urlencode($appId)
                . '&wrap_client_secret=' . urlencode($appSecret)
                . '&wrap_callback=' . urlencode($callbackUrl)
                . '&wrap_verification_code=' . urlencode($verificationCode);
        $response = $this->postWRAPRequest($authUrl, $tokenRequest);
        return $this->parseWRAPResponse($response);
    }
 
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
 
    private function parseWRAPResponse($response)
    { 
        $pos = strpos($response, 'wrap_access_token=');
        if ($pos === false)
        {
            $pos = strpos($response, 'wrap_error_reason=');
        }
        $codes = '?' . substr($response, $pos, strlen($response));
 
        if (preg_match_all('/[?&]([^&=]+)=([^&=]+)/', $codes, $matches))
        {
            for($i =0; $i < count($matches[1]); $i++)
            { 
                $contents[$matches[1][$i]] = $matches[2][$i];
            }
        }
        else
        {
            throw new Exception('No matches for regular expression.');
        }
        return $contents;
    }
 
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
 
	public function MakeUrl($url,$params){
		if(!empty($params) && $params){
			foreach($params as $k=>$v) $kv[] = "$k=$v";
			$url_params = str_replace(" ","+",implode('&',$kv));
			$url = trim($url) . '?' . $url_params;
		}
		return $url;
	}
}
