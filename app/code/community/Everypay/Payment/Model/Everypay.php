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
    protected $_canFetchTransactionInfo = false;

    /**
     * Send capture request to gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @param decimal $amount
     *
     * @throws Mage_Core_Exception
     *
     * @return Everypay_Payment_Model_Everypay
     */
    public function capture(Varien_Object $payment, $amount)
    {
        return $this->captureOrPurchase($payment);
    }

    /**
     * Send authorize request to gateway
     *
     * @param  Mage_Payment_Model_Info $payment
     * @param  decimal $amount
     *
     * @throws Mage_Core_Exception
     *
     * @return Everypay_Payment_Model_Everypay
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $token = $this->getEverypayToken();

        // Set transaction open so can be captured later.
        $payment->setIsTransactionClosed(false);

        try {
            $gateway = $this->getGateway($payment);
            $response = $gateway->authorize($this->buildAuthorizeRequest($payment));
        } catch (Exception $e) {
            $this->throwException();
        }

        return $this->auditResponse($payment, $response);
    }

    /**
     * Refund the amount with transaction id
     *
     * @param Mage_Payment_Model_Info $payment
     * @param decimal $amount
     *
     * @throws Mage_Core_Exception
     *
     * @return Everypay_Payment_Model_Everypay
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $transId = $payment->getLastTransId();
        $transId = str_replace('cpt_', null, $transId);

        try {
            $gateway = $this->getGateway($payment);
            $response = $gateway->refund($transId, $this->getAmount($amount));
        } catch (Exception $e) {
            $this->throwException($e->getMessage());
        }

        return $this->auditResponse($payment, $response);
    }

    /**
     * Void an authorized payment through gateway
     *
     * @param  Mage_Payment_Model_Info $payment
     *
     * @throws Mage_Core_Exception
     *
     * @return Everypay_Payment_Model_Everypay
     */
    public function void(Varien_Object $payment)
    {
        $transId = $payment->getLastTransId();

        try {
            $gateway = $this->getGateway($payment);
            $response = $gateway->void($transId);
        } catch (Exception $e) {
            $this->throwException($e->getMessage());
        }

        return $this->auditResponse($payment, $response);
    }

    private function captureOrPurchase(Varient_Object $payment)
    {
        if ($transId = $payment->getLastTransId()) {
            // Capture authorized payment
            return $this->captureOnline($transId, $payment);
        }

        return $this->purchase($payment);
    }

    private function captureOnline($transId, Varient_Object $payment)
    {
        try {
            $gateway = $this->getGateway($payment);
            $response = $gateway->capture($transId);
        } catch (Exception $e) {
            $this->throwException();
        }

        $transId = 'cpt_' . $response->token;

        return $this->auditResponse($payment, $response, $transId);
    }

    private function purchase(Varien_Object $payment)
    {
        try {
            $gateway = $this->getGateway($payment);
            $response = $gateway->purchase($this->buildPurchaseRequest($payment));
        } catch (Exception $e) {
            $this->throwException();
        }

        return $this->auditResponse($payment, $response);
    }

    private function buildPurchaseRequest(Varien_Object $payment)
    {
        return $this->getNewPaymentParams($payment);
    }

    private function buildAuthorizeRequest(Varient_Object $payment)
    {
        $params = $this->getNewPaymentParams($payment);
        $params['capture'] = 0;

        return $params;
    }

    /**
     * Get params to send to gateway for a new payment.
     *
     * @param Varien_Object $payment
     *
     * @return array
     */
    private function getNewPaymentParams($payment)
    {
        $order = $payment->getOrder();

        $installments = new Everypay_Payment_Model_Installments();
        $maxInstallments = $installments->getMaxInstallments($order->getBaseGrandTotal());

        return array(
            'token' => $this->getEverypayToken(),
            'amount' => $this->getAmount($order->getBaseGrandTotal()),
            'description' => $this->getDescription($order),
            'payee_email' => $this->getPayeeEmail($order),
            'payee_phone' => $this->getPayeePhone($order),
            'max_installments' => $maxInstallments,
        );
    }

    /**
     * Formats decimal amount to cents.
     *
     * @param decimal $amount
     *
     * @return integer
     */
    private function getAmount($amount)
    {
        return round($amount, 2) * 100;
    }

    private function auditResponse($payment, $response, $transId = null)
    {
        if ($response->isSuccess()) {
            return $this->auditSuccess($payment, $response, $transId);
        }

        return $this->auditFailed($payment, $response);
    }

    private function auditSuccess($payment, $response, $transId = null)
    {
        $payment->setTransactionId($this->resolveTransId($response, $transId));
        $this->setTransactionDetails($payment, $response);

        return $this;
    }

    private function resolveTransId($response, $transId)
    {
        if (!empty($response->refunds)) {
            $refund = array_pop($response->refunds);

            return $refund->token;
        }

        return $transId ?: $response->token;
    }

    private function setTransactionDetails($payment, $response)
    {
        $_data = array();

        $_data['status'] = $response->status;
        $_data['fee amount'] = number_format($response->fee_amount / 100, 2);
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

    private function auditFailed($payment, $response)
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

    private function getEverypayToken()
    {
        $post = Mage::app()->getRequest()->getParams();

        $token = isset($post['everypay_token'])
            ? $post['everypay_token']
            : false;

        if (false === $token) {
            $this->throwException('Invalid or missing payment data. Please try again.');
        }

        return $token;
    }

    private function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function getGateway($payment)
    {
        $store = $payment->getOrder()->getStore(); //Mage::app()->getStore();
        $sandbox = Mage::getStoreConfig('payment/everypay/sandbox', $store);
        $secretKey = Mage::getStoreConfig('payment/everypay/secret_key', $store);

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
