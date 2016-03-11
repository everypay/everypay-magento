<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Model_Gateway
{
    const LIVE_URL = 'https://api.everypay.gr';
    const TEST_URL = 'https://sandbox-api.everypay.gr';

    const PURCHASE = '/payments';
    const AUTHORIZE = '/payments';

    private $secretKey;

    private $isTest = false;

    private $response;

    public function __construct($secretKey, $sandbox)
    {
        $this->secretKey = $secretKey;

        $this->isTest = (bool) $sandbox;
    }

    public function purchase($token, array $data)
    {
        return $this->charge(self::PURCHASE, $token, $data);
    }

    public function authorize($token, array $data)
    {
        $data['capture'] = 0;

        return $this->charge(self::AUTHORIZE, $token, $data);
    }

    private function charge($action, $token, array $data)
    {
        $data['token'] = $token;

        return $this->commit($action, $data);
    }

    private function commit($action, $data)
    {
        $url = $this->isTest ? self::TEST_URL : self::LIVE_URL;

        $url .= $action;

        $postString = http_build_query($data);

        $responseString = $this->send($url, $postString);

        return new Everypay_Payment_Model_GatewayResponse(json_decode($responseString));
    }

    private function send($url, $data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ":");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $response;
    }
}