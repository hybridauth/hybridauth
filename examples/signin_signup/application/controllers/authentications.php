<?php
class authentications extends controller {
	function authenticatewith( $provider )
	{
		// set on application.config.php
		GLOBAL $hybridauth_config;

		try{
		// create an instance for Hybridauth with the configuration file path as parameter
			$hybridauth = new Hybrid_Auth( $hybridauth_config );

		// try to authenticate the selected $provider
			$adapter = $hybridauth->authenticate( $provider );

		// grab the user profile
			$user_profile = $adapter->getUserProfile();

		// load user and authentication models, we will need them...
			$authentication = $this->loadModel( "authentication" );
			$user = $this->loadModel( "user" );

		# 1 - check if user already have authenticated using this provider before
			$authentication_info = $authentication->find_by_provider_uid( $provider, $user_profile->identifier );

		# 2 - if authentication exists in the database, then we set the user as connected and redirect him to his profile page
			if( $authentication_info ){
				// 2.1 - store user_id in session
				$_SESSION["user"] = $authentication_info["user_id"]; 

				// 2.2 - redirect to user/profile
				$this->redirect( "users/profile" );
			}

		# 3 - else, here lets check if the user email we got from the provider already exists in our database ( for this example the email is UNIQUE for each user )
			// if authentication does not exist, but the email address returned  by the provider does exist in database, 
			// then we tell the user that the email  is already in use 
			// but, its up to you if you want to associate the authentification with the user having the adresse email in the database
			if( $user_profile->email ){
				$user_info = $user->find_by_email( $user_profile->email );

				if( $user_info ) {
					die( '<br /><b style="color:red">Well! the email returned by the provider ('. $user_profile->email .') already exist in our database, so in this case you might use the <a href="index.php?route=users/login">Sign-in</a> to login using your email and password.</b>' );
				}
			}

		# 4 - if authentication does not exist and email is not in use, then we create a new user 
			$provider_uid  = $user_profile->identifier;
			$email         = $user_profile->email;
			$first_name    = $user_profile->firstName;
			$last_name     = $user_profile->lastName;
			$display_name  = $user_profile->displayName;
			$website_url   = $user_profile->webSiteURL;
			$profile_url   = $user_profile->profileURL;
			$password      = rand( ) ; # for the password we generate something random

			// 4.1 - create new user
			$new_user_id = $user->create( $email, $password, $first_name, $last_name ); 

			// 4.2 - creat a new authentication for him
			$authentication->create( $new_user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $profile_url, $website_url );
 
			// 4.3 - store the new user_id in session
			$_SESSION["user"] = $new_user_id;

			// 4.4 - redirect to user/profile
			$this->redirect( "users/profile" );
		}
		catch( Exception $e ){
			// Display the recived error
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

			// well, basically your should not display this to the end user, just give him a hint and move on..
			$error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage(); 
			$error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>"; 

			// load error view
			$data = array( "error" => $error ); 
			$this->loadView( "pages/error", $data );
		}
	}
}
