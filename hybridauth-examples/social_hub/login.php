<?php
	session_start();

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
			// change the following paths if necessary 
			$config = dirname(__FILE__) . '/../../hybridauth/config.php';
			require_once( "../../hybridauth/Hybrid/Auth.php" );

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

			// Display the recived error, 
			// to know more please refer to Exceptions handling section on the userguide
			switch( $e->getCode() ){ 
				case 0 : $error = "Unspecified error."; break;
				case 1 : $error = "Hybriauth configuration error."; break;
				case 2 : $error = "Provider not properly configured."; break;
				case 3 : $error = "Unknown or disabled provider."; break;
				case 4 : $error = "Missing provider application credentials."; break;
				case 5 : $error = "Authentification failed. The user has canceled the authentication or the provider refused the connection."; break;
				case 6 : $error = "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again."; 
					     $adapter->logout(); 
					     break;
				case 7 : $error = "User not connected to the provider."; 
					     $adapter->logout(); 
					     break;
			} 

			// make sure you show these to the end user
			$error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage(); 
			$error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>";
		}
    endif; 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
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

<table width="740" border="0" cellpadding="2" cellspacing="2">
  <tr>
   <td align="left" valign="top"> 
		<fieldset>
        <legend>Sign-in form</legend>
        <table border="0" cellpadding="2" cellspacing="2">
          <tr>
            <td><div align="right"><strong>login</strong></div></td>
            <td><input type="text" name="textfield" id="textfield" disabled /></td>
          </tr>
          <tr>
            <td><div align="right"><strong>password</strong></div></td>
            <td><input type="text" name="textfield2" id="textfield2" disabled /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td> 
                <input type="submit" value="Um supposed to be a Submit button, but i do nothing" style="width:350px;height:32px;" disabled />
            </td>
          </tr>
        </table>
      </fieldset>
	</td>
    <td align="left" valign="top"> 
		<fieldset>
        <legend>Or use one of thoses providers</legend>
			&nbsp;&nbsp;<a href="?provider=Google">Sign-in with Google</a><br /> 
			&nbsp;&nbsp;<a href="?provider=Yahoo">Sign-in with Yahoo</a><br /> 
			&nbsp;&nbsp;<a href="?provider=Facebook">Sign-in with Facebook</a><br />
			&nbsp;&nbsp;<a href="?provider=Twitter">Sign-in with Twitter</a><br />
			&nbsp;&nbsp;<a href="?provider=MySpace">Sign-in with MySpace</a><br />  
			&nbsp;&nbsp;<a href="?provider=Live">Sign-in with Windows Live</a><br />  
			&nbsp;&nbsp;<a href="?provider=LinkedIn">Sign-in with LinkedIn</a><br /> 
      </fieldset> 
	</td>
  </tr> 
</table>

	<br />
	<br />
	
<table width="60%" border="0" cellspacing="10">
	<tr>  
	<td>
	<hr /> 
	This exapmle show how users can login with providers using <b>HybridAuth</b>. It also show how to grab their profile, update their status or to grab their freinds list from services like facebook, twitter, myspace.
	<br />
	<br />
	If you want even more providers please goto to HybridAuth web site and download the <a href="http://hybridauth.sourceforge.net/download.html">Additional Providers Package</a>.
	</td>
	</tr>
</table>
</html>