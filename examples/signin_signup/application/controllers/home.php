<?php
class home extends controller {
	// let move to the subject... to signin signup users
	function index()
	{
		$this->redirect( "users/login" );
	}
}
