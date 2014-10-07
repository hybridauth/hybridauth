<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en">
<head>
	<style type="text/css" media="screen">
		<!--
			BODY {
				margin: 10px;
				padding: 0;
			}
			H1 {
				margin-bottom: 2px;
				font-family: Garamond, "Times New Roman", Times, Serif;
			}
			FIELDSET {
				border: 1px solid #ccc;
				padding: 1em;
				margin: 0;
			}
			LEGEND {
				color: #666666;
			} 
		-->
	</style> 
</head>
<body>
	<br /><br />
	<center>
		<h1>Welcome <?php echo $user_data->displayName; ?></h1>
		<br />
		<br /> 
 
		<table width="600" border="0" cellpadding="2" cellspacing="2">
		  <tr>
			<td valign="top"><fieldset>
				<legend>Connected user badge</legend>
				<table width="100%" border="0" cellpadding="2" cellspacing="2"> 
				  <tr> 
					<td width="100" rowspan="5">
						<?php
							if( $user_data->photoURL ){
						?>
							<a href="<?php echo $user_data->profileURL; ?>"><img src="<?php echo $user_data->photoURL; ?>" title="<?php echo $user_data->displayName; ?>" border="0" width="100" height="120"></a>
						<?php
							}
							else{
						?> 
							<img src="avatar.png" title="<?php echo $user_data->displayName; ?>" border="0" >
						<?php
							} 
						?>
					</td>
					<td width="60" nowrap><div align="right"><strong>Provider</strong></div></td>
					<td align="left" ><?php echo $adapter->id; ?></td>
				  </tr> 
				  <tr>
					<td nowrap><div align="right"><strong>Identifier</strong></div></td>
					<td align="left"><?php echo $user_data->identifier; ?></td>
				  </tr>
				  <tr>
					<td nowrap><div align="right"><strong>Display name</strong></div></td>
					<td align="left"><?php echo $user_data->displayName; ?></td>
				  </tr>
				  <tr>
					<td nowrap><div align="right"><strong>Email</strong></div></td>
					<td align="left"><?php 
							if( $user_data->email ){
								echo $user_data->email ; 
							}
							else{
								echo "not provided by " . $adapter->id ; 
							}
						?></td>
				  </tr>
				  <tr>
					<td nowrap valign="top"><div align="right"><strong>Profile URL</strong></div></td>
					<td align="left" valign="top"><?php echo $user_data->profileURL; ?></td>
				  </tr>
				</table> 
                
			  </fieldset></td>
		  </tr>
		  <tr>
			<td valign="top" align="right">
				<img src="arrow.gif" align="texttop" style="margin-top:-5px;" >
				<a href="?logout=<?php echo $adapter->id; ?>">Log me out</a>
			</td>
		  </tr>
		</table>
 

		
	</center> 
</body>
</html>