<?php
	// config and whatnot
    $config = dirname(__FILE__) . '/../../hybridauth/config.php';
    require_once( "../../hybridauth/Hybrid/Auth.php" );

	$user_data = NULL;

	// try to get the user profile from an authenticated provider
	try{
		$hybridauth = new Hybrid_Auth( $config );

		// selected provider name 
		$provider = @ trim( strip_tags( $_GET["provider"] ) );

		// check if the user is currently connected to the selected provider
		if( !  $hybridauth->isConnectedWith( $provider ) ){ 
			// redirect him back to login page
			header( "Location: login.php?error=Your are not connected to $provider or your session has expired" );
		}

		// call back the requested provider adapter instance (no need to use authenticate() as we already did on login page)
		$adapter = $hybridauth->getAdapter( $provider );

		// grab the user profile
		$user_data = $adapter->getUserProfile();
    }
	catch( Exception $e ){  
		// In case we have errors 6 or 7, then we have to use Hybrid_Provider_Adapter::logout() to 
		// let hybridauth forget all about the user so we can try to authenticate again.

		// Display the received error,
		// to know more please refer to Exceptions handling section on the userguide
		switch( $e->getCode() ){ 
			case 0 : echo "Unspecified error."; break;
			case 1 : echo "Hybriauth configuration error."; break;
			case 2 : echo "Provider not properly configured."; break;
			case 3 : echo "Unknown or disabled provider."; break;
			case 4 : echo "Missing provider application credentials."; break;
			case 5 : echo "Authentication failed. " 
					  . "The user has canceled the authentication or the provider refused the connection."; 
			case 6 : echo "User profile request failed. Most likely the user is not connected "
					  . "to the provider and he should to authenticate again."; 
				   $adapter->logout(); 
				   break;
			case 7 : echo "User not connected to the provider."; 
				   $adapter->logout(); 
				   break;
		} 

		echo "<br /><br /><b>Original error message:</b> " . $e->getMessage();

		echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>";  
	}
?>
<!DOCTYPE html>
<html lang="en">
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="public/css.css" type="text/css">
</head>
<body>  
<?php
	if( $user_data ){
?> 
<table width="90%" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td valign="top">
		<?php
			include "includes/menu.php";
		?> 
		<fieldset>
        <legend>Profile information</legend>
        <table width="100%">
          <tr>
            <td width="150" valign="top" align="center">
				<?php
					if( $user_data->photoURL ){
				?>
					<a href="<?php echo $user_data->profileURL; ?>"><img src="<?php echo $user_data->photoURL; ?>" title="<?php echo $user_data->displayName; ?>" border="0" width="100" height="120"></a>
				<?php
					}
					else{
				?> 
				<img src="public/avatar.png" title="<?php echo $user_data->displayName; ?>" border="0" >
				<?php
					} 
				?>  
			</td>
            <td align="left"><table width="100%" cellspacing="0" cellpadding="3" border="0">
                <tbody>
                  <tr>
                    <td width="10%">providerID</td>
                    <td width="83%">&nbsp; <?php echo $adapter->id; ?></td>
                  </tr>
                  <tr>
                    <td width="10%">identifier</td>
                    <td width="83%">&nbsp; <?php echo $user_data->identifier; ?></td>
                  </tr> 
                  <tr>
                    <td>profileURL</td>
                    <td>&nbsp; <a href="<?php echo $user_data->profileURL; ?>"><?php echo $user_data->profileURL; ?></a></td>
                  </tr>
                  <tr>
                    <td>webSiteURL</td>
                    <td>&nbsp; <?php echo $user_data->webSiteURL; ?></td>
                  </tr>
                  <tr>
                    <td>photoURL</td>
                    <td>&nbsp; <?php echo $user_data->photoURL; ?></td>
                  </tr>
                  <tr>
                    <td>displayName</td>
                    <td>&nbsp; <?php echo $user_data->displayName; ?></td>
                  </tr>
                  <tr>
                    <td>description</td>
                    <td>&nbsp; <?php echo $user_data->description; ?></td>
                  </tr>
                  <tr>
                    <td>firstName</td>
                    <td>&nbsp; <?php echo $user_data->firstName; ?></td>
                  </tr>
                  <tr>
                    <td>lastName</td>
                    <td>&nbsp; <?php echo $user_data->lastName; ?></td>
                  </tr>
                  <tr>
                    <td>gender</td>
                    <td>&nbsp; <?php echo $user_data->gender; ?></td>
                  </tr>
                  <tr>
                    <td>language</td>
                    <td>&nbsp; <?php echo $user_data->language; ?></td>
                  </tr>
                  <tr>
                    <td>age</td>
                    <td>&nbsp; <?php echo $user_data->age; ?></td>
                  </tr>
                  <tr>
                    <td>birthDay</td>
                    <td>&nbsp; <?php echo $user_data->birthDay; ?></td>
                  </tr>
                  <tr>
                    <td>birthMonth</td>
                    <td>&nbsp; <?php echo $user_data->birthMonth; ?></td>
                  </tr>
                  <tr>
                    <td>birthYear</td>
                    <td>&nbsp; <?php echo $user_data->birthYear; ?></td>
                  </tr>
                  <tr>
                    <td>email</td>
                    <td>&nbsp; <?php echo $user_data->email; ?></td>
                  </tr>
                  <tr>
                    <td>phone</td>
                    <td>&nbsp; <?php echo $user_data->phone; ?></td>
                  </tr>
                  <tr>
                    <td>address</td>
                    <td>&nbsp; <?php echo $user_data->address; ?></td>
                  </tr>
                  <tr>
                    <td>country</td>
                    <td>&nbsp; <?php echo $user_data->country; ?></td>
                  </tr>
                  <tr>
                    <td>region</td>
                    <td>&nbsp; <?php echo $user_data->region; ?></td>
                  </tr>
                  <tr>
                    <td>city</td>
                    <td>&nbsp; <?php echo $user_data->city; ?></td>
                  </tr>
                  <tr>
                    <td>zip</td>
                    <td>&nbsp; <?php echo $user_data->zip; ?></td>
                  </tr>
                </tbody>
              </table> 
			  </td>
          </tr>  
        </table>
		</fieldset>
	</td>
    <td valign="top" width="250" align="left"> 
		<?php
			include "includes/sidebar.php";
		?>
	</td>
  </tr>
</table>  
<?php
	} // if( $user_data )

	include "includes/debugger.php";
?> 
</body>
</html>
