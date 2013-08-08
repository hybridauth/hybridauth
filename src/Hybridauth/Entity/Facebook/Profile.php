<?php
namespace Hybridauth\Entity\Facebook;

use Hybridauth\Http\Request;

class Profile extends \Hybridauth\Entity\Profile
{
    function getPhotoUrl($width = 150, $height = 150) {
        return isset($this->photoURL) ?
                $this->photoURL :
                sprintf('https://graph.facebook.com/' . $this->getIdentifier() . '/picture?width=%d&height=%d',$width,$height);
    }

    function postEvent(Event &$event) {
        $eventIdentifier = $event->getIdentifier();
        $uri = (empty($eventIdentifier) ? $this->getIdentifier() . '/events' : $eventIdentifier);
        //This logic should probably live in Event... Maybe a function we pass the adapter to
        $parameters = array();
        if(!is_null($x = $event->getName()))        $parameters['name']         = $x;
        if(!is_null($x = $event->getDescription())) $parameters['description']  = $x;
        if(!is_null($x = $event->getStartTime()))   $parameters['start_time']   = $x;
        if(!is_null($x = $event->getEndTime()))     $parameters['end_time']     = $x;
        if(!is_null($x = $event->getLocation()))    $parameters['location']     = $x;
        if(!is_null($x = $event->getStreet()))      $parameters['street']       = $x;
        if(!is_null($x = $event->getZip()))         $parameters['zip']          = $x;
        if(!is_null($x = $event->getCountry()))     $parameters['country']      = $x;
        if(!is_null($x = $event->getLatitude()))    $parameters['latitude']     = $x;
        if(!is_null($x = $event->getLongitude()))   $parameters['longitude']    = $x;
        if(!is_null($x = $event->getTicketURI()))   $parameters['ticket_uri']   = $x;
        $response = $this->getAdapter()->signedRequest($uri,Request::POST, $parameters);
        $response = json_decode( $response );
        if( isset($response->error) ) return false;
        if( isset($response->id) && empty($eventIdentifier))
        {
            $event->setIdentifier($response->id);
        }
        return true;
    }
}
