<fieldset>
	<legend>Well ignore this, just some debuggin and staff</legend> 
	<b><?php echo $adapter->id; ?> session access tokens</b>
	<pre><?php print_r( $adapter->getAccessToken() ); ?></pre> 
	
	<b>Session debug</b>
	<pre><?php print_r( $_SESSION ); ?></pre> 
</fieldset> 