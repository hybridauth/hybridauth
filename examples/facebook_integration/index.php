<?php
	session_start();

	// include hybridauth lib
	$config = dirname(__FILE__) . '/../../hybridauth/config.php';
	require_once( "../../hybridauth/Hybrid/Auth.php" );

	// start login with facebook?
	if( isset( $_GET["login"] ) ){
		try{
			$hybridauth = new Hybrid_Auth( $config );

			$adapter = $hybridauth->authenticate( "facebook" );

			$user_profile = $adapter->getUserProfile();
		}
		catch( Exception $e ){
			die( "<b>got an error!</b> " . $e->getMessage() ); 
		}
	}

	// logged in ?
	if( ! isset( $user_profile ) ){
?>
<p>
A VERY basic example showing how to integrate Facebook Javascript SDK side by side with HybridAuth. Click the "Sign in" link to start.
</p>

<h2><a href ="index.php?login=1">Sign in with facebook</a></h2> 
<?php
	}
	
	// user signed in with facebook
	else{
?>
<!DOCTYPE html>
<html lang="en">
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="../social_hub/public/css.css" type="text/css">
<script src="https://raw.github.com/douglascrockford/JSON-js/master/json2.js"></script> 
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js"></script> 
<style>
pre{width:450px;overflow:auto;}
</style>
</head>
<body>  
 
<table width="95%" border="0" cellpadding="2" cellspacing="2">
<tr>
<td width="50%" valign="top">

<fieldset> 
<legend>Hybridauth library</legend>
<b>Hi <?php echo $user_profile->displayName; ?></b>, <br />your profile url is: <?php echo $user_profile->profileURL; ?>
<hr />
<b>Hybridauth access tokens for Facebook:</b>
<pre><?php print_r( $adapter->getAccessToken() ); ?></pre> 
</fieldset> 

</td>
<td width="50%" valign="top">

<fieldset> 
<legend>Facebook JavaScript SDK</legend>  
You are logged into this site with Facebook and the <a href='https://developers.facebook.com/docs/reference/javascript/'>Javascript SDK</a> and you should be happy about that.
<br />
<br />
<span id="hellomessage"></span>  
<hr />
<input value="Click on me to share this page on Facebook" style="height:30px;" type="submit" onclick="share_link()" /><br />
<input value="Click on me to publish a random story your Facebook wall" style="height:30px;" type="submit" onclick="post_to_wall()" /><br />
<input value="Click on me to invite friends" style="height:30px;" type="submit" onclick="invite_friends()" />
 
<hr /> 

<br /> 
Note: Inviting friends <b>may require some advanced</b> Facebook application configuration your side. To know more about FB.ui visit <a href='https://developers.facebook.com/docs/reference/javascript/FB.ui/'>https://developers.facebook.com/docs/reference/javascript/FB.ui/</a>
 
<div id="fb-root"></div> 
<script src="http://connect.facebook.net/en_US/all.js"></script>  

<script> 
$(function(){ 
	FB.init({ 
		appId:'<?php echo $adapter->config["keys"]["id"]; ?>', // or simply set your appid hard coded
		cookie:true, 
		status : true,
		xfbml:true
	});

	FB.getLoginStatus(function(response) {
		console.log( response );
		$("#hellomessage").after( "<br /><hr /><b>Facebook JavaScript SDK Login Status :</b>:<pre>" + JSON.stringify( response ) + "</pre>" );

		FB.api('/me', function(response) {
			console.log( response );
			$("#hellomessage").html( "<b>Hi " + response.name + "</b>,<br />your profile url is: " + response.link );
		}); 
	});
});

// https://developers.facebook.com/docs/reference/dialogs/send/
function share_link() { 
	FB.ui({
		method: 'send',
		name: 'HybridAuth, open source social sign on php library',
		link: 'http://hybridauth.sourceforge.net/',
	});
}

// https://developers.facebook.com/docs/reference/dialogs/requests/
function invite_friends() {  
	FB.ui({
		method: 'apprequests', message: '<?php echo $user_profile->displayName; ?> want you to join him at mywebsite.com.'
	});
}

// https://developers.facebook.com/docs/reference/dialogs/feed/
function post_to_wall() {  
	var obj = {
		method: 'feed',
		link: 'http://hybridauth.sourceforge.net/',
		picture: 'http://fbrell.com/f8.jpg',
		name: 'HybridAuth',
		caption: 'HybridAuth, open source social sign on php library',
		description: 'HybridAuth, open source social sign on php library.'
	};

	function callback(response) {
		document.getElementById('msg').innerHTML = "Post ID: " + response['post_id'];
	}

	FB.ui(obj, callback);
}

function o2s (obj) {
    var str = '';
    for (var p in obj) {
        if (obj.hasOwnProperty(p)) {
            str += p + '::' + obj[p] + '\n';
        }
    }
    return str;
} 
</script> 


</fieldset> 

</tr>
</table>
</body>
</html>
<?php
	}
