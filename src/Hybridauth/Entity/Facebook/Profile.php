<?php
namespace \Hybrid\Entity\Facebook;

use \Hybrid\Entity\Profile;

class Profile extends \Hybrid\Entity\Profile {
    function getPhotoUrl($width = 150, $height = 150) {
        return isset($this->photoURL) ?
                $this->photoURL :
                sprintf('https://graph.facebook.com/' . $this->getIdentifier() . '/picture?width=%d&height=%d',$width,$height);
    }
}
