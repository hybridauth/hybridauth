<?php

class Hybrid_AutoLoader
{
  public static function doAutoload( $className )
  {
    // 17 = strlen("Hybrid_Providers_")
    if ( substr($className, 0, 17) == "Hybrid_Providers_" ) {
      require realpath(dirname(__FILE__)) . "/Providers/" . substr($className, 17) . ".php";
    }
    // 7 = strlen("Hybrid_")
    else if ( substr($className, 0, 7) == "Hybrid_" ) {
      require realpath(dirname(__FILE__)) . "/" . substr($className, 7) . ".php";
    }
  }
  
  public static function registerAutoLoadFunction()
  {
    spl_autoload_register(array(__CLASS__, "doAutoload"));
  }
}

Hybrid_AutoLoader::registerAutoLoadFunction();

?>