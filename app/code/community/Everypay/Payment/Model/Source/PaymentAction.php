<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Model_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Everypay_Payment_Model_Everypay::ACTION_AUTHORIZE,
                'label' => Mage::Helper('everypay')->__('Authorize Only')
            ),
            array(
                'value' => Everypay_Payment_Model_Everypay::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('everypay')->__('Authorize and Capture')
            ),
        );
    }
}
