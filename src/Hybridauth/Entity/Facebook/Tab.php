<?php
namespace Hybridauth\Entity\Facebook;

use \Hybridauth\Http\Request;

class Tab extends \Hybridauth\Entity\Entity
{
    protected $link = null;
    protected $name = null;

    public function getLink() {
        return $this->link;
    }

    public function getName() {
        return $this->name;
    }

    public function setLink($link) {
        $this->link = $link;
    }

    public function setName($name) {
        $this->name = $name;
    }

    function delete() {
        $identifier = $this->getIdentifier();
        if(empty($identifier)) return true;
        $response = $this->getAdapter()->signedRequest('/'.$identifier,Request::DELETE);
        $response = json_decode($response);
        if(isset($response->error) || $response === false) return false;
        $this->setIdentifier(null);
        return true;
    }

    public static function generateFromResponse($response,$adapter=null) {
        $r = parent::generateFromResponse($response,$adapter);
        $r->setIdentifier ( static::parser( 'id',$response )   );
        $r->setName       ( static::parser( 'name',$response ) );
        $r->setLink       ( static::parser( 'link',$response ) );
        return $r;
    }
}
