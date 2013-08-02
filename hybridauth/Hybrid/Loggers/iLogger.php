<?php
interface Hybrid_Loggers_iLogger {
    function __construct($config);
    public static function debug( $message, $object = NULL );
    public static function info( $message );
    public static function error( $message, $object = NULL );
}
