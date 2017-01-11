<?
if(!function_exists('curl_init')){print ("cURL library cannot be found. Make sure it is installed."); exit;}

	$agent = "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://www.google.com/adsense/");
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$returned=curl_exec ($ch);

	
	if($returned==null){
	echo "Your cURL does not allow https protocol. Make sure OpenSSL is installed.<br/>
	Details Error :<br/><b>".curl_error($ch)."</b>";
	}else{
	echo "Your cURL is working properly. hybridauth should work on your server.";
	}

	curl_close ($ch);
?>