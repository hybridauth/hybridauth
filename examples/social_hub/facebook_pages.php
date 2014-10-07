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
			<legend>Post feed to Facebook pages</legend>   
			<?php
				try{
					$adapter = $hybridauth->getAdapter( $provider );

					// ask facebook api for the users accounts
					$accounts = $adapter->api()->api('/me/accounts');

					if( ! count( $accounts["data"] ) ){
					?>
						<p>
							NO pages found for the current user! 
						</p>
						<p>
							<b>Note</b>: To be able to post to facebook pages you should:
						</p>
						<ol>
							<li>Add <b>"manage_pages"</b> to the requested scope in the configuration,</li>
							<li>Logout from Facebook provider,</li>
							<li>Re sign-in with Facebook.</li>
						</ol> 
					<?php
					}
					else{
					?>	
						<form action="" method="post">
							<table width="100%" border="0">
							  <tr>
								<td width="80">Page</td>
								<td>
									<select name="page_id" style="width:658px;">
										<?php foreach( $accounts["data"] as $account ){ ?>
											<option value="<?php echo $account['id']; ?>"><?php echo $account['name']; ?></option>
										<?php } ?> 
									</select>
								</td>
							  </tr>
							  <tr>
								<td valign="top">Message</td>
								<td><textarea name="message" style="width:650px;height:80px;"></textarea></td>
							  </tr>
							  <tr>
								<td>&nbsp;</td>
								<td><input type="submit" style="width:658px;height:32px;" value="Send" /></td>
							  </tr>
							  <tr>
								<td colspan="2" align="center">
									<br />
									<?php
										if( isset( $_POST["page_id"] ) ){
											$message = trim( $_POST["message"] ); 

											// is message not empty
											if( empty( $message ) ){
												echo "<b style='color:red'>Write somthing on the textarea.</b>";
											}
											else{
												$page_id    = $_POST["page_id"];
												$page_token = "";

												foreach( $accounts['data'] as $account ){
													if( $account['id'] == $page_id ){
														$page_token = $account['access_token'];
													}
												}

												$attachment = array(
													'access_token' => $page_token,
													'message' => $message,
													'name' => "Hybridauth Tiny Social Hub - Test",
													'link' => 'http://hybridauth.sourceforge.net/' 
												);

												// ask facebook api to post the message to the selected page
												$adapter->api()->api( "/$page_id/feed", 'POST', $attachment );

												echo "<b style='color:green'>Sent!</b>";
											} 
										}
									?>
								</td>
							  </tr>
							</table> 
						</form>
					<?php  
					}
				}
				catch( Exception $e ){
					echo "<b style='color:red'>Well, got an error:</b> " . $e->getMessage();
				} 
			?>  
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