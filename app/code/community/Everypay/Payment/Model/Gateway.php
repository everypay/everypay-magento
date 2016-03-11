<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Model_Gateway
{
    const LIVE_URL = 'https://api.everypay.gr';
    const TEST_URL = 'https://sandbox-api.everypay.gr';

    const PURCHASE = '/payments';
    const AUTHORIZE = '/payments';
    const CAPTURE = '/payments/capture/%s';
    const REFUND = '/payments/refund/%s';
    const VOID = '/payments/refund/%s';

    private $secretKey;

    private $isTest = false;

    private $response;

    public function __construct($secretKey, $sandbox)
    {
        $this->secretKey = $secretKey;

        $this->isTest = (bool) $sandbox;
    }

    public function purchase(array $data)
    {
        return $this->charge(self::PURCHASE, $data);
    }

    public function authorize(array $data)
    {
        return $this->charge(self::AUTHORIZE, $data);
    }

    public function capture($token)
    {
        $action = sprintf(self::CAPTURE, $token);

        return $this->commit($action);
    }

    public function refund($token, $amount)
    {
        $action = sprintf(self::REFUND, $token);

        return $this->commit($action, ['amount' => $amount]);
    }

    public function void($token)
    {
        $action = sprintf(self::REFUND, $token);

        return $this->commit($action);
    }

    private function charge($action, array $data)
    {
        return $this->commit($action, $data);
    }

    private function commit($action, array $data = array())
    {
        $url = $this->isTest ? self::TEST_URL : self::LIVE_URL;

        $url .= $action;

        $postString = empty($data) ? null : http_build_query($data);

        $response = $this->send($url, $data);

        $responseString = $response->getBody();

        return new Everypay_Payment_Model_GatewayResponse(json_decode($responseString));
    }

    private function send($url, $data)
    {
        $client = new Varien_Http_Client();
        $client->setUri($url);
        $client->setConfig(array(
            'maxredirects' => 0,
            'timeout' => 60,
            'useragent' => 'Everypay Magento Plugin/0.1.0 (+https://www.everypay.gr)'
        ));

        $client->setParameterPost($data);
        $client->setMethod(Zend_Http_Client::POST);
        $client->setAuth($this->secretKey);

        return $client->request();
    }
}
