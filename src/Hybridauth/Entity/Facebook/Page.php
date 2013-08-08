<?php
namespace Hybridauth\Entity\Facebook;

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
        $parameters['app_id'] = is_object($x) ? $x->getIdentifier() : $x;
        $response = $this->getAdapter()->signedRequest($uri,Request::POST, $parameters);
        $response = json_decode( $response );
        if( isset($response->error) ) return false; //throw exception?
        return true;
    }

    function getTab($tab_id) {
        $tab = $this->getTabs($tab_id);
        if($tab && count($tab)) return reset($tab);
        return false;
    }

    function getTabs($tab_id = null) {
        $return = array();

        $uri = 'me/tabs' . isset($tab_id) ? '/'.(is_object($tab_id) ? $tab_id->getIdentifier() : $tab_id) : '';
        $response = $this->getAdapter()->signedRequest($uri);
        $response = json_decode( $response );
        if ( ! isset( $response->data ) || isset ( $response->error ) ){
            throw new
                Exception(
                    'Tab listing failed: Provider returned an invalid response. ' .
                    'HTTP client state: (' . $this->httpClient->getState() . ')',
                    Exception::USER_PROFILE_REQUEST_FAILED,
                    $this
                );
        }

        foreach($response->data as $tabData) {
            $return[] = Tab::generateFromResponse($tabData,$this->getAdapter());
        }

        return $return;
    }
}
