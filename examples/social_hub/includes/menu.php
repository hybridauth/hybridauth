<fieldset>
	<legend>Actions</legend>

	<a href="profile.php?provider=<?php echo $_GET["provider"]; ?>">Show my profile</a> -
	<a href="status.php?provider=<?php echo $_GET["provider"]; ?>">Update status</a> -
	<?php if( strtolower( $_GET["provider"] ) == "facebook" ){ ?>
		<a href="facebook_pages.php?provider=<?php echo $_GET["provider"]; ?>"><b>Facebook Pages</b></a> -
	<?php } ?>
	<a href="timeline.php?provider=<?php echo $_GET["provider"]; ?>">My timeline</a> -
	<a href="activity.php?provider=<?php echo $_GET["provider"]; ?>">My activity stream</a>  -
	<a href="contacts.php?provider=<?php echo $_GET["provider"]; ?>">My contacts list</a>  -
	<a href="logout.php?provider=<?php echo $_GET["provider"]; ?>">Logout</a>
</fieldset>
