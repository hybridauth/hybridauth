<?php
namespace Hybridauth\Entity\Twitter;

use Hybridauth\Entity\Twitter\Profile;

class Post extends \Hybridauth\Entity\Post
{
    public function getLink() {
        if(isset($this->link)) return $this->link;
        $owner = $this->getFrom();
        if(!is_object($owner)) return null;
        if(is_null($owner = $owner->getDisplayName())) return null;
        $this->setLink(sprintf('http://twitter.com/%s/status/%s',$owner,$this->getIdentifier()));
        return $this->link;
    }

    public static function generateFromResponse($response,$adapter) {
        $post = parent::generateFromResponse($response,$adapter);

        $post->setIdentifier   ( static::parser( 'id_str',                    $response ) );
        $post->setInResponseTo ( static::parser( 'in_reply_to_status_id_str', $response ) );
        $post->setMessage      ( static::parser( 'text',                      $response ) );
        $post->setTime         ( static::parser( 'created_at',                $response ) );
        if($user = static::parser('user',$response)) {
            $post->setFrom(Profile::generateFromResponse($user,$adapter));
        }
        return $post;
    }
}
