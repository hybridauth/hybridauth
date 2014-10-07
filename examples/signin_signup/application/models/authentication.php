<?php
class authentication extends model { 
	function find_by_provider_uid( $provider, $provider_uid ){
		$sql = "SELECT * FROM authentications WHERE provider = '$provider' AND provider_uid = '$provider_uid' LIMIT 1";

		$result = mysql_query_excute($sql);

		return mysql_fetch_assoc($result);
	}

	function create( $user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $profile_url, $website_url ){ 
		$sql = "INSERT INTO authentications ( user_id, provider, provider_uid, email, display_name, first_name, last_name, profile_url, website_url, created_at ) VALUES ( '$user_id', '$provider', '$provider_uid', '$email', '$display_name', '$first_name', '$last_name', '$profile_url', '$website_url', NOW() ) ";

		mysql_query_excute($sql);

		return mysql_insert_id();
	} 
	
	function find_by_user_id( $user_id ){ 
		$sql = "SELECT * FROM authentications WHERE user_id = '$user_id' LIMIT 1";

		$result = mysql_query_excute($sql);
 
		return mysql_fetch_assoc($result);
	} 
}
