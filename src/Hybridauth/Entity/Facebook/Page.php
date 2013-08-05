<?php
namespace \Hybrid\Entity\Facebook;

class Page extends Profile {
    protected $accessToken = null;

    protected $permissions = null;

    protected $category    = null;

    function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
    }

    function setCategory($category) {
        $this->category = $category;
    }

    function setPermissions($permissions) {
        $this->permissions = $permissions;
    }

    function getAccessToken() {
        return $this->accessToken;
    }

    function getCategory() {
        return $category;
    }

    function getPermissions() {
        return $this->permissions;
    }
}
