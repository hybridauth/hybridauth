<?php
namespace Hybridauth\Entity\Facebook;

class Profile extends \Hybridauth\Entity\Profile {
    function getPhotoUrl($width = 150, $height = 150) {
        return isset($this->photoURL) ?
                $this->photoURL :
                sprintf('https://graph.facebook.com/' . $this->getIdentifier() . '/picture?width=%d&height=%d',$width,$height);
    }
}
