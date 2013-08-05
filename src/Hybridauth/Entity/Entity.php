<?php
namespace Hybridauth\Entity;

class Entity
{
    protected $adapter = null;
    function __construct($adapter = null) {
        $this->setAdapter($adapter);
    }

    function setAdapter($adapter) {
        $this->adapter = $adapter;
    }

    function getAdapter() {
        return $this->adapter;
    }
}
