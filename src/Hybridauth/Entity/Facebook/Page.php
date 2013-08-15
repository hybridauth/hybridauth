<?php
namespace Hybridauth\Entity\Facebook;

use Hybridauth\Exception;
use \Hybridauth\Http\Request;

class Page extends Profile
{
    protected $permissions = null;
    protected $category    = null;

    function setAccessToken($accessToken) {
        $adapter = $this->getAdapter();
        $adapter = clone $adapter;
        $adapter->setFacebookAccessToken($accessToken);
        $this->setAdapter($adapter);
    }

    function setCategory($category) {
        $this->category = $category;
    }

    function setPermissions($permissions) {
        $this->permissions = $permissions;
    }

    function getAccessToken() {
        return $this->getAdapter()->getFacebookAccessToken();
    }

    function getCategory() {
        return $category;
    }

    function getPermissions() {
        return $this->permissions;
    }

    function installApplication($app_id) {
        $uri = 'me/tabs';
        $parameters = array();
        $parameters['app_id'] = is_object($app_id) ? $x->getIdentifier() : $app_id;
        $response = $this->getAdapter()->signedRequest($uri,Request::POST, $parameters);
        $response = json_decode( $response );
        if( isset($response->error) ) return false; //throw exception?
        return true;
    }

    function getTab($tab_id) {
        $result = $this->getTabs($tab_id);
        if($result && count($result)) return reset($result);
        return false;
    }

    function getTabs($tab_id = null) {
        $return = array();

        $uri = 'me/tabs' . (is_null($tab_id) ? '' : ('/' . (is_numeric($tab_id) ? $tab_id : $tab_id->getIdentifier())));
        $orig_response = $this->getAdapter()->signedRequest($uri);
        $response = json_decode( $orig_response );
        if ( ! isset( $response->data ) || isset ( $response->error ) ){
            throw new
                Exception(
                    'Tab listing failed: Provider returned an invalid response. ' .
                    'HTTP client state: (' . $orig_response . ')',
                    Exception::USER_PROFILE_REQUEST_FAILED,
                    $this
                );
        }

        foreach($response->data as $tabData) {
            $return[] = Tab::generateFromResponse($tabData,$this->getAdapter());
        }

        return $return;
    }

    public static function generateFromResponse($response,$adapter) {
        $page = parent::generateFromResponse($response,$adapter);
        $page->setPermissions( static::parser( 'perms',$response        ) );
        $page->setAccessToken( static::parser( 'access_token',$response ) );
        $page->setCategory   ( static::parser( 'category',$response     ) );
        return $page;
    }
}
