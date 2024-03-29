<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Block_Button extends Mage_Core_Block_Template
{
    protected $_params = array();

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('everypay/button.phtml');
        $this->getFormFields();
    }

    public function setParams($params)
    {
        $this->_params = $params;
        return $this;
    }

    public function getParams()
    {
        return $this->_params;
    }

    private function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function getFormFields()
    {
        $order = $this->getCheckout()->getQuote();
        $amount = round($order->getBaseGrandTotal(), 2);
        $currencyCode = $order->getBaseCurrencyCode();
        $store = Mage::app()->getStore();

        $fields = array();
        $fields['sandbox'] = Mage::getStoreConfig('payment/everypay/sandbox', $store);
        $fields['public_key'] = Mage::getStoreConfig('payment/everypay/public_key', $store);
        $fields['order_id'] = $order->getLastRealOrderId();
        $fields['customer_email'] = $order->getData('customer_email');
        $fields['submit_url'] = Mage::getUrl('everypay/payment/review', array('_secure' => true));
        $fields['currency_code'] = $currencyCode;
        $fields['amount'] = $amount * 100;
        $fields['store_name'] = str_replace(array("\r", "\n"), "", $order->getData('store_name'));

        $this->_params = $fields;

        $maxInstallments = 0;
        if (false !== $max = $this->getMaxInstallments($amount)) {
            $maxInstallments = $max;
        }
        $this->_params['max_installments'] = $maxInstallments;
    }

    private function getMaxInstallments($total)
    {
        $installments = new Everypay_Payment_Model_Installments();

        return $installments->getMaxInstallments($total);
    }
}
