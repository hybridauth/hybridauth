<?php
	// config and whatnot
    $config = dirname(__FILE__) . '/../../hybridauth/config.php';
    require_once( "../../hybridauth/Hybrid/Auth.php" );

	// initialise hybridauth
	$hybridauth = new Hybrid_Auth( $config );
	
	// selected provider name 
	$provider = @ trim( strip_tags( $_GET["provider"] ) );

	// check if the user is currently connected to the selected provider
	if( !  $hybridauth->isConnectedWith( $provider ) ){ 
		// redirect him back to login page
		header( "Location: login.php?error=Your are not connected to $provider or your session has expired" );
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="public/css.css" type="text/css">
</head>
<body>  
<table width="90%" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td valign="top">
		<?php
			include "includes/menu.php"; 
		?>  
		<fieldset>
			<legend>Update your status</legend> 
			<b>Important</b>: 
			<br />
			1- Currently only <b>Facebook, Twitter and LinkedIn</b> do support this feature! Please refer to the user guide to know more about each adapters capabilities. <a href='http://hybridauth.sourceforge.net/userguide.html'>http://hybridauth.sourceforge.net/userguide.html</a>
			<br /> 
			2- Also, some providers will truncate the status. for example, the text of the status update must be 140 characters max with twitter.
			<hr /> 
			<br />
			<center>
			<form action="" method="post">
			<table width="650" border="0">
			  <tr>
				<td>
					Wazzaup?
					<textarea name="status" style="width:650px;height:120px;"></textarea>
				</td> 
			  </tr>
			  <tr> 
				<td align="right"><input type="submit" style="width:200px;height:32px;" value="Update" /></td>
			  </tr>
			  <tr> 
				<td align="left"> 
					<?php
						try{
							// if form submitted
							if( isset( $_POST["status"] ) ){
								$status = trim( $_POST["status"] ); 

								// is status not empty
								if( empty( $status ) ){
									echo "<b style='color:red'>Write somthing on the textarea. please.</b>";
								}
								else{
									// call back the requested provider adapter instance 
									$adapter = $hybridauth->getAdapter( $provider );

									// update user staus
									$adapter->setUserStatus( $status );

									echo "<b style='color:green'>Status updated. You should be able to check it on 'My activity stream' section or by refering to you profile page on $provider</b>";
								}
							}
						}
						catch( Exception $e ){
							// if code 8 => Provider does not support this feature
							if( $e->getCode() == 8 ){
								echo "<b style='color:red'>Error: Provider does not support this feature.</b> Currently only <b>Facebook, MySpace, Twitter, Identica and LinkedIn</b> do support this!
								<br />Please refer to the user guide to know more about each adapters capabilities. <a href='http://hybridauth.sourceforge.net/userguide.html'>http://hybridauth.sourceforge.net/userguide.html</a>";
							}
							else{
								echo "<b style='color:red'>Well, got an error:</b> " . $e->getMessage();
							} 
						} 
					?> 
				</td>
			  </tr>
			</table>
			</form>
			</center>
      </fieldset> 
	</td>
    <td valign="top" width="250" align="left"> 
		<?php
			include "includes/sidebar.php";
		?>
	</td>
  </tr>
</table>
</body>
</html>