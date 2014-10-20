<?php 
	session_start(); 

	// change the following paths if necessary 
	$config = dirname(__FILE__) . '/../../hybridauth/config.php';
	require_once( "../../hybridauth/Hybrid/Auth.php" );

	// check for erros and whatnot
	$error = "";
	
	if( isset( $_GET["error"] ) ){
		$error = '<b style="color:red">' . trim( strip_tags(  $_GET["error"] ) ) . '</b><br /><br />';
	}

	// if user select a provider to login with
		// then inlcude hybridauth config and main class
		// then try to authenticate te current user
		// finally redirect him to his profile page
	if( isset( $_GET["provider"] ) && $_GET["provider"] ):
		try{
			// create an instance for Hybridauth with the configuration file path as parameter
			$hybridauth = new Hybrid_Auth( $config );
 
			// set selected provider name 
			$provider = @ trim( strip_tags( $_GET["provider"] ) );

			// try to authenticate the selected $provider
			$adapter = $hybridauth->authenticate( $provider );

			// if okey, we will redirect to user profile page 
			$hybridauth->redirect( "profile.php?provider=$provider" );
		}
		catch( Exception $e ){
			// In case we have errors 6 or 7, then we have to use Hybrid_Provider_Adapter::logout() to 
			// let hybridauth forget all about the user so we can try to authenticate again.

			// Display the received error,
			// to know more please refer to Exceptions handling section on the userguide
			switch( $e->getCode() ){ 
				case 0 : $error = "Unspecified error."; break;
				case 1 : $error = "Hybriauth configuration error."; break;
				case 2 : $error = "Provider not properly configured."; break;
				case 3 : $error = "Unknown or disabled provider."; break;
				case 4 : $error = "Missing provider application credentials."; break;
				case 5 : $error = "Authentication failed. The user has canceled the authentication or the provider refused the connection."; break;
				case 6 : $error = "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again."; 
					     $adapter->logout(); 
					     break;
				case 7 : $error = "User not connected to the provider."; 
					     $adapter->logout(); 
					     break;
			} 

			// well, basically your should not display this to the end user, just give him a hint and move on..
			$error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage(); 
			$error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>";
		}
    endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="public/css.css" type="text/css">
</head>
<body>
<center>
<br />
<h1>Hybridauth Tiny Social Hub</h1> 

<?php
	// if we got an error then we display it here
	if( $error ){
		echo '<p><h3 style="color:red">Error!</h3>' . $error . '</p>';
		echo "<pre>Session:<br />" . print_r( $_SESSION, true ) . "</pre><hr />";
	}
?>
<br />

<table width="500" border="0" cellpadding="2" cellspacing="2">
  <tr> 
    <td align="left" valign="top"> 
		<fieldset>
        <legend>Sign-in with one of these providers</legend>
			&nbsp;&nbsp;<a href="?provider=Google">Sign-in with Google</a><br /> 
			&nbsp;&nbsp;<a href="?provider=Yahoo">Sign-in with Yahoo</a><br /> 
			&nbsp;&nbsp;<a href="?provider=Facebook">Sign-in with Facebook</a><br />
			&nbsp;&nbsp;<a href="?provider=Twitter">Sign-in with Twitter</a><br />
			&nbsp;&nbsp;<a href="?provider=Live">Sign-in with Windows Live</a><br />  
			&nbsp;&nbsp;<a href="?provider=LinkedIn">Sign-in with LinkedIn</a><br /> 
			&nbsp;&nbsp;<a href="?provider=Foursquare">Sign-in with Foursquare</a><br /> 
			&nbsp;&nbsp;<a href="?provider=AOL">Sign-in with AOL</a><br />  
      </fieldset> 
	</td> 
<?php 
	// try to get already authenticated provider list
	try{
		$hybridauth = new Hybrid_Auth( $config );

		$connected_adapters_list = $hybridauth->getConnectedProviders(); 

		if( count( $connected_adapters_list ) ){
?> 
    <td align="left" valign="top">  
		<fieldset>
			<legend>Providers you are logged with</legend>
			<?php
				foreach( $connected_adapters_list as $adapter_id ){
					echo '&nbsp;&nbsp;<a href="profile.php?provider=' . $adapter_id . '">Switch to <b>' . $adapter_id . '</b>  account</a><br />'; 
				}
			?> 
		</fieldset> 
	</td>		
<?php
		}
	}
	catch( Exception $e ){
		echo "Ooophs, we got an error: " . $e->getMessage();

		echo " Error code: " . $e->getCode();

		echo "<br /><br />Please try again.";

		echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>"; 
	}
?> 
  </tr> 
</table>

	<br />
	<br />
	
<table width="60%" border="0" cellspacing="10">
	<tr>  
	<td>
	<hr /> 
	This example show how users can login with providers using <b>HybridAuth</b>. It also show how to grab their profile, update their status or to grab their freinds list from services like facebook, twitter.
	<br />
	<br />
	If you want even more providers please goto to HybridAuth web site and download the <a href="http://hybridauth.sourceforge.net/download.html">Additional Providers Package</a>.
	</td>
	</tr>
</table>
</html>
