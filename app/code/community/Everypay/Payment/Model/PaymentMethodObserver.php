<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Model_PaymentMethodObserver extends Mage_Core_Block_Abstract
{
    public function outputEverypayButton($observer)
    {
        if (isset($_POST['payment']['method']) && $_POST['payment']['method'] == "everypay") {
            $controller = $observer->getEvent()->getData('controller_action');
            $result = Mage::helper('core')->jsonDecode(
                $controller->getResponse()->getBody('default'),
                Zend_Json::TYPE_ARRAY
            );

            if (empty($result['error'])) {
                $controller->loadLayout('checkout_onepage_review');
                $block = $controller->getLayout()->createBlock('everypay/button');
                $html = $block->toHtml();

                $parentHtml = $controller->getLayout()->getBlock('root')->toHtml();
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $html . $parentHtml
                );
                $result['redirect'] = false;
                $result['success'] = true;
                $controller->getResponse()->clearHeader('Location');

                $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        }
        return $this;
    }
}
