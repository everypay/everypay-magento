<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
