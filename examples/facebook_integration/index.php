<?php
	session_start();

	$config = dirname(__FILE__) . '/../../hybridauth/config.php';
	require_once( "../../hybridauth/Hybrid/Auth.php" );

	if( isset( $_GET["login"] ) ){
		try{
			// hybridauth EP
			$hybridauth = new Hybrid_Auth( $config );

			$adapter = $hybridauth->authenticate( "facebook" );

			$user_profile = $adapter->getUserProfile();
		}
		catch( Exception $e ){
			die( "<b>got an error!</b> " . $e->getMessage() ); 
		}
	}

	if( ! isset( $user_profile ) ){
?>
<p>
A basic example which show how to integrate Facebook Dialogs and stuff on your website side by side whith HybridAuth. Click the Signin link to start.
</p>

	<h2><a href ="index.php?login=1">Signin with facebook</a></h2>

	<img src="../fb.gif" style="border:1px solid #ccc;padding:4px;" />
<?php
	}
	else{
?>
<h3>Hi <?php echo $user_profile->displayName; ?> </h3>

<input value="Want to share this page on facebook?" style="height:30px;" type="submit" onclick="share_link()" />
<input value="Want to publish a random story your facebook wall?" style="height:30px;" type="submit" onclick="post_to_wall()" />
<input value="Want to invite some friends ?" style="height:30px;" type="submit" onclick="invite_friends()" />

<p>
	<hr />
	The invite friends <b>may require some advanced</b> facebook application configuration your side. To know more about FB.ui visit https://developers.facebook.com/docs/reference/javascript/FB.ui/ 
</p>

<div id="fb-root"></div> 
<script src="http://connect.facebook.net/en_US/all.js"></script> 
<script> 
FB.init({ 
	appId:'<?php echo $adapter->config["keys"]["id"]; ?>', // or simply set your appid hard coded
	cookie:true, 
	status : true,
	xfbml:true
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
</script> 
<?php
	}
