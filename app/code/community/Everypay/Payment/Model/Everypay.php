<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Model_Everypay extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'everypay';
    protected $_formBlockType = 'everypay/form_token';

    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    protected $_canFetchTransactionInfo = true;

    public function capture(Varien_Object $payment, $amount)
    {
        $post = Mage::app()->getRequest()->getParams();
        if (false === ($token = $this->getEverypayToken($post))) {
            $this->throwException('Invalid or missing payment data. Please try again.');
        }

        $this->processPayment($token, $payment);

        return $this;
    }

    public function authorize(Varien_Object $payment, $amount)
    {

    }

    private function processPayment($token, $payment)
    {
        $order = $payment->getOrder();
        $sandbox = Mage::getStoreConfig('payment/everypay/sandbox');

        $secretKey = Mage::getStoreConfig('payment/everypay/secret_key');
        $amount = round($order->getGrandTotal(), 2) * 100;

        $gateway = $this->getGateway($secretKey, $sandbox);

        $params = array(
            'amount' => $amount,
            'description' => $this->getDescription($order),
            'payee_email' => $this->getPayeeEmail($order),
            'payee_phone' => $this->getPayeePhone($order),
        );

        try {
            $response = $gateway->purchase($token, $params);
        } catch (Exception $e) {
            $this->throwException();
        }

        if ($response->isSuccess()) {
            return $this->auditSuccessPayment($payment, $response);
        }

        return $this->auditFailedPayment($payment, $response);
    }

    private function auditSuccessPayment($payment, $response)
    {
        $payment->setTransactionId($response->token);
        $this->setTransactionDetails($payment, $response);
    }

    private function setTransactionDetails($payment, $response)
    {
        $_data = array();

        $_data['status'] = $response->status;
        $_data['fee_amount'] = number_format($response->fee_amount / 100, 2);
        $_data['installments'] = $response->installments_count;
        $_data['card'] = str_pad($response->card->last_four, 16, '*', STR_PAD_LEFT);
        $_data['card expiration'] = sprintf('%s / %s', $response->card->expiration_month, $response->card->expiration_year);
        $_data['card type'] = $response->card->type;
        $_data['holder name'] = $response->card->holder_name;

        $payment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $_data
        );
    }

    private function auditFailedPayment($payment, $response)
    {
        $this->setErrorTransactionDetails($payment, $response);
        $this->throwException($response->error->message);
    }

    private function setErrorTransactionDetails($payment, $response)
    {
        $_data = (array) $response->error;

        $payment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $_data
        );
    }

    private function throwException($message = 'Request to Everypay Failed.')
    {
        Mage::throwException($message);
    }

    private function getEverypayToken($post)
    {
        return isset($post['everypay_token'])
            ? $post['everypay_token']
            : false;
    }

    private function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function getGateway($secretKey, $sandbox = false)
    {
        return new Everypay_Payment_Model_Gateway($secretKey, $sandbox);
    }

    private function getDescription($order)
    {
        return sprintf(
            "%s #%s %s €",
            $this->getStoreName($order),
            $order->getRealOrderId(),
            number_format(round($order->getGrandTotal(), 2), 2)
        );
    }

    private function getStoreName($order)
    {
        $store = $order->getStore();
        if ($group = $store->getGroup()) {
            return $group->getName();
        }

        return str_replace(array("\r", "\n"), "", $order->getData('store_name'));
    }

    private function getPayeeEmail($order)
    {
        return $order->getBillingAddress()->getEmail();
    }

    private function getPayeePhone($order)
    {
        return $order->getBillingAddress()->getTelephone();
    }
}
