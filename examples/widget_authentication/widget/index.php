<?php
	// start a new session (required for Hybridauth)
	session_start(); 

	// change the following paths if necessary 
	$config   = dirname(__FILE__) . '/../../../hybridauth/config.php';
	require_once( "../../../hybridauth/Hybrid/Auth.php" );

	try{ 
		$hybridauth = new Hybrid_Auth( $config );
	} 
	// if sometin bad happen
	catch( Exception $e ){
		$message = ""; 
		
		switch( $e->getCode() ){ 
			case 0 : $message = "Unspecified error."; break;
			case 1 : $message = "Hybriauth configuration error."; break;
			case 2 : $message = "Provider not properly configured."; break;
			case 3 : $message = "Unknown or disabled provider."; break;
			case 4 : $message = "Missing provider application credentials."; break;
			case 5 : $message = "Authentication failed. The user has canceled the authentication or the provider refused the connection."; break;

			default: $message = "Unspecified error!";
		}
?>
<style>
PRE {
	background:#EFEFEF none repeat scroll 0 0;
	border-left:4px solid #CCCCCC;
	display:block;
	padding:15px;
	overflow:auto;
	width:740px;
}
HR {
	width:100%;
	border: 0;
	border-bottom: 1px solid #ccc; 
	padding: 50px;
}
</style>
<table width="100%" border="0">
  <tr>
    <td align="center"><br /><img src="images/icons/alert.png" /></td>
  </tr>
  <tr>
    <td align="center"><br /><h3>Something bad happen!</h3><br /></td> 
  </tr>
  <tr>
    <td align="center">&nbsp;<?php echo $message ; ?><hr /></td> 
  </tr>
  <tr>
    <td>
		<b>Exception</b>: <?php echo $e->getMessage() ; ?>
		<pre><?php echo $e->getTraceAsString() ; ?></pre>
	</td> 
  </tr>
</table> 
<?php 
		// diplay error and RIP
		die();
	}

	$provider  = @ $_GET["provider"];
	$return_to = @ $_GET["return_to"];
	
	if( ! $return_to ){
		echo "Invalid params!";
	}

	if( ! empty( $provider ) && $hybridauth->isConnectedWith( $provider ) )
	{
		$return_to = $return_to . ( strpos( $return_to, '?' ) ? '&' : '?' ) . "connected_with=" . $provider ;
?>
<script language="javascript"> 
	if(  window.opener ){
		try { window.opener.parent.$.colorbox.close(); } catch(err) {} 
		window.opener.parent.location.href = "<?php echo $return_to; ?>";
	}

	window.self.close();
</script>
<?php
		die();
	}

	if( ! empty( $provider ) )
	{
		$params = array();

		if( $provider == "OpenID" ){
			$params["openid_identifier"] = @ $_REQUEST["openid_identifier"];
		}

		if( isset( $_REQUEST["redirect_to_idp"] ) ){
			$adapter = $hybridauth->authenticate( $provider, $params );
		}
		else{
			// here we display a "loading view" while tryin to redirect the user to the provider
?>
<table width="100%" border="0">
  <tr>
    <td align="center" height="190px" valign="middle"><img src="images/loading.gif" /></td>
  </tr>
  <tr>
    <td align="center"><br /><h3>Loading...</h3><br /></td> 
  </tr>
  <tr>
    <td align="center">Contacting <b><?php echo ucfirst( strtolower( strip_tags( $provider ) ) ) ; ?></b>. Please wait.</td> 
  </tr> 
</table>
<script>
	window.location.href = window.location.href + "&redirect_to_idp=1";
</script>
<?php
		}

		die();
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>HybridAuth Social Sing-on</title> 
	<style>
		.idpico{
			cursor: pointer;
			cursor: hand;
		}
		#openidm{
			margin: 7px;
		}
	</style>
	<script src="js/jquery.min.js"></script>
	<script>
		var idp = null;

		$(function() { 
			$(".idpico").click(
				function(){ 
					idp = $( this ).attr( "idp" );

					switch( idp ){
						case "google"  : case "twitter" : case "yahoo" : case "facebook": case "aol" : 
						case "vimeo" : case "tumblr" : case "lastfm" : case "twitter" : 
						case "linkedin" : 
										start_auth( "?provider=" + idp );
										break; 
						case "wordpress" : case "blogger" : case "flickr" :  case "livejournal" :  
										if( idp == "blogger" ){
											$("#openidm").html( "Please enter your blog name" );
										}
										else{
											$("#openidm").html( "Please enter your username" );
										}
										$("#openidun").css( "width", "220" );
										$("#openidimg").attr( "src", "images/icons/" + idp + ".png" );
										$("#idps").hide();
										$("#openidid").show();  
										break;
						case "openid" : 
										$("#openidm").html( "Please enter your OpenID URL" );
										$("#openidun").css( "width", "350" );
										$("#openidimg").attr( "src", "images/icons/" + idp + ".png" );
										$("#idps").hide();
										$("#openidid").show();  
										break;

						default: alert( "u no fun" );
					}
				}
			); 

			$("#openidbtn").click(
				function(){
					oi = un = $( "#openidun" ).val();

					if( ! un ){
						alert( "nah not like that! put your username or blog name on this input field." );
						
						return false;
					}

					switch( idp ){ 
						case "wordpress" : oi = "http://" + un + ".wordpress.com"; break;
						case "livejournal" : oi = "http://" + un + ".livejournal.com"; break;
						case "blogger" : oi = "http://" + un + ".blogspot.com"; break;
						case "flickr" : oi = "http://www.flickr.com/photos/" + un + "/"; break;   
					}
					
					start_auth( "?provider=OpenID&openid_identifier=" + escape( oi ) ); 
				}
			);  

			$("#backtolist").click(
				function(){
					$("#idps").show();
					$("#openidid").hide();

					return false;
				}
			);  
		});

		function start_auth( params ){
			start_url = params + "&return_to=<?php echo urlencode( $return_to ); ?>" + "&_ts=" + (new Date()).getTime();
			window.open(
				start_url, 
				"hybridauth_social_sing_on", 
				"location=0,status=0,scrollbars=0,width=800,height=500"
			);  
		}
	</script> 
</head>
<body>
	<div id="idps">
		<table width="100%" border="0">
		  <tr>
			<td align="center"><img class="idpico" idp="google" src="images/icons/google.png" title="google" /></td>
			<td align="center"><img class="idpico" idp="twitter" src="images/icons/twitter.png" title="twitter" /></td>
			<td align="center"><img class="idpico" idp="facebook" src="images/icons/facebook.png" title="facebook" /></td>
			<td align="center"><img class="idpico" idp="openid" src="images/icons/openid.png" title="openid" /></td>  
		  </tr>
		  <tr>
			<td align="center"><img class="idpico" idp="yahoo" src="images/icons/yahoo.png" title="yahoo" /></td>
			<td align="center"><img class="idpico" idp="flickr" src="images/icons/flickr.png" title="flickr" /></td>
			<td align="center"><img class="idpico" idp="linkedin" src="images/icons/linkedin.png" title="linkedin" /></td>
		  </tr>
		  <tr> 
			<td align="center"><img class="idpico" idp="blogger" src="images/icons/blogger.png" title="blogger" /></td> 
			<td align="center"><img class="idpico" idp="wordpress" src="images/icons/wordpress.png" title="wordpress" /></td>
			<td align="center"><img class="idpico" idp="livejournal" src="images/icons/livejournal.png" title="livejournal" /></td>  
		  </tr>
		</table> 
	</div>
	<div id="openidid" style="display:none;">
		<table width="100%" border="0">
		  <tr> 
			<td align="center"><img id="openidimg" src="images/loading.gif" /></td>
		  </tr>  
		  <tr> 
			<td align="center"><h3 id="openidm">Please enter your user or blog name</h3></td>
		  </tr>  
		  <tr>
			<td align="center"><input type="text" name="openidun" id="openidun" style="padding: 5px; margin:7px;border: 1px solid #999;width:240px;" /></td>
		  </tr>
		  <tr>
			<td align="center">
				<input type="submit" value="Login" id="openidbtn" style="height:33px;width:85px;" />
				<br />
				<small><a href="#" id="backtolist">back</a></small>
			</td>
		  </tr>
		</table> 
	</div>
</body>
</html>
