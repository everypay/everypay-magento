<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Model_GatewayResponse
{
    private $reponse;

    private $success = false;

    public function __construct($response)
    {
        $this->response = $response;

        $this->success = $this->response && !isset($this->response->error);
    }

    public function getMessage()
    {
        return isset($this->response->error)
            ? $this->response->error->message
            : $this->response->status;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function __get($name)
    {
        return isset($this->response->$name)
            ? $this->response->$name
            : null;
    }
}
