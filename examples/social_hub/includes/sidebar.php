<!-- 1/ show how to user getConnectedProviders --> 
<?php 
	// try to get already authenticated provider list
	try{
		$connected_adapters_list = $hybridauth->getConnectedProviders(); 

		if( count( $connected_adapters_list ) ){
?> 
		<fieldset>
			<legend>Providers you are logged with</legend>
			<?php
				foreach( $connected_adapters_list as $adapter_id ){
					echo '&nbsp;&nbsp;<a href="profile.php?provider=' . $adapter_id . '">Switch to <b>' . $adapter_id . '</b>  account</a><br />'; 
				}
			?>
			<br />
			&nbsp;&nbsp;<a href="logout_all.php">Logout all connected providers</a>
		</fieldset> 
<?php
		}
	}
	catch( Exception $e ){
		echo "Ooophs, we got an error: " . $e->getMessage();

		echo " Error code: " . $e->getCode();

		echo "<br /><br />Please try again.";

		echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>"; 
	}
?> 

<!-- 2/ show how to user isConnectedWith --> 
<fieldset>
	<legend>Try another one ?</legend>

	<?php
		$available_providers_list = array( 
			"Google"    ,
			"Yahoo"     ,
			"Facebook"  ,
			"Twitter"   ,
			"Live"      ,
			"LinkedIn"  ,
			"Foursquare",
			"AOL"       ,
		);

		foreach( $available_providers_list as $adapter_id ){
			if( ! $hybridauth->isConnectedWith( $adapter_id ) ){
				?>
					&nbsp;&nbsp;<a href="login.php?provider=<?php echo $adapter_id ?>">Sign-in with <?php echo $adapter_id ?></a><br /> 
				<?php
			}
		}
	?> 
</fieldset> 
