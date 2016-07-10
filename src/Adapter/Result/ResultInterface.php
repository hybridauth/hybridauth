<?php

namespace Hybridauth\Adapter\Result;

/**
 * Provides a way to determine the result of authentication.
 *
 * @author Alrick Telfer <alrick@unihost.com.jm>
 */
interface ResultInterface {
    const RESULT_TYPE_SUCCESS = 'result_success';
    const RESULT_TYPE_REDIRECT_REQUEST = 'result_redirect_request';
    const RESULT_TYPE_ERROR = 'result_error';

    /**
     * Sets the result type
     *
     * @param string $type
     */
    public function setType(string $type);

    /**
     * @return string Returns result type
     */
    public function getType();

    /**
     * Sets data associated with the result
     *
     * @param mixed $data
     */
    public function setData($data);

    /**
     * @return mixed May return any kind of object that will represent the result
     */
    public function getData();
}
