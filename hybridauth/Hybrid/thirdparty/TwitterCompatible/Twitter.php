<?php 
	class Twitter_Client extends Twitter_Compatible_Client
	{
		/* Set up the API root URL. */
		public $host = "https://api.twitter.com/1/";

		/* Set API URLS */
		function accessTokenURL()  { return 'https://api.twitter.com/oauth/access_token'; }
		function authenticateURL() { return 'https://api.twitter.com/oauth/authenticate'; }
		function authorizeURL()    { return 'https://api.twitter.com/oauth/authorize'; }
		function requestTokenURL() { return 'https://api.twitter.com/oauth/request_token'; }
	}
