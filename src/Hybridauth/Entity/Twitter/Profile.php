<?php
namespace Hybridauth\Entity\Twitter;

use Hybridauth\Entity\Twitter\Post;

class Profile extends \Hybridauth\Entity\Profile
{
    protected $lastPost = null;

    function getLastPost() {
        return $this->lastPost;
    }

    function setLastPost($lastPost) {
        $this->lastPost = $lastPost;
    }

    function getProfileURL() {
        return isset($this->profileURL) ?
                $this->profileURL :
                ('http://twitter.com/' . $this->getDisplayName());
    }

    public static function generateFromResponse($response,$adapter) {
        $profile = parent::generateFromResponse($response,$adapter);
        $profile->setIdentifier ( static::parser( 'id_str',$response            ) );
        $profile->setFirstName  ( static::parser( 'name',$response              ) );
        $profile->setDisplayName( static::parser( 'screen_name',$response       ) );
        $profile->setDescription( static::parser( 'description',$response       ) );
        $profile->setPhotoURL   ( static::parser( 'profile_image_url',$response ) );
        $profile->setWebSiteURL ( static::parser( 'url',$response               ) );
        $profile->setRegion     ( static::parser( 'location',$response          ) );

        if($post = static::parser('status',$response)) {
            $profile->setLastPost(Post::generateFromResponse($post,$adapter));
            $profile->getLastPost()->setFrom($profile);
        }

        return $profile;
    }
}
