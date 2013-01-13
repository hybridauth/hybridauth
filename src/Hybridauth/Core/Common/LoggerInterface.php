<?php 
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

interface Hybridauth_Core_Common_LoggerInterface
{
    public function info($message, $extra = array());

    public function error($message, $extra = array());

    public function debug($message, $extra = array());
}
