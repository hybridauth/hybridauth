<?php 
	class Identica_Client extends Twitter_Compatible_Client
	{
		/* Set up the API root URL. */
		public $host = "https://identi.ca/api/"; 

		/* Set API URLS */ 
		function authenticateURL() { return 'https://identi.ca/api/oauth/authorize'; } 
	}
