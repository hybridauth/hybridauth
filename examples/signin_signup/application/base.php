<?php
// almost nothing to see here... just basic mvc stuff
class application
{
	public $uri; 

	function __construct( $uri = null )
	{
		$this->uri = $uri;

		$this->loadController( $uri['controller'] );
	}

	function loadController( $class )
	{
		$file = "application/controllers/".$this->uri['controller'].".php";

		if(!file_exists($file)) die( "controller not found at $file" );

		require_once($file);

		$controller = new $class();

		if( method_exists( $controller, $this->uri['method'] ) ){
			$controller->{$this->uri['method']}( $this->uri['var'] );
		} 
		else {
			$controller->index();
		}
	}
}

class model
{ 
	function __construct(){}
}

class controller
{
	function loadModel( $model )
	{
		require_once( 'application/models/'. $model .'.php' );
		return new $model;
	} 

	function loadView( $view, $vars="" )
	{
		if(is_array($vars) && count($vars) > 0) extract($vars, EXTR_PREFIX_SAME, "wddx");
		require_once( 'application/views/'.$view.'.html' );
	}

	function redirect( $uri )
	{
		header( "Location: index.php?route=$uri" );
		
		die();
	}
}
