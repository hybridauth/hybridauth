<?php

namespace Hybridauth\Adapter\Result;

/**
 * @author Alrick Telfer <alrick@unihost.com.jm>
 */
class AuthResult implements ResultInterface {
    /**
     * The type of result
     * @var string
     */
    protected $type;

    /**
     * Data representing the result
     * @var mixed
     */
    protected $data;

    /**
     * Constructs a new AuthResult
     * @param type $type
     * @param type $data
     */
    function __construct($type, $data) {
        $this->type = $type;
        $this->data = $data;
    }


    public function getData() {
        return $this->data;
    }

    public function getType() {
        return $this->type;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function setType(string $type) {
        $this->type = $type;
    }

}
