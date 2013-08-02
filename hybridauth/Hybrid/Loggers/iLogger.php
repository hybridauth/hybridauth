<?php
interface Hybrid_Loggers_iLogger {
    function __construct($config);
    public function debug( $message, $object = NULL );
    public function info( $message );
    public function error($message, $object = NULL);
}
