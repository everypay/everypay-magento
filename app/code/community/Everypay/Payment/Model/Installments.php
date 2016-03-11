<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class Everypay_Payment_Model_Installments
{
    public function getMaxInstallments($total)
    {
        $installmentsString = Mage::getStoreConfig('payment/everypay/installments');
        $inst = htmlspecialchars_decode($installmentsString);
        if ($inst) {
            $installments = json_decode($inst, true);
            foreach ($installments as $i) {
                if ($total >= $i['from'] && $total <= $i['to']) {
                    return $i['max'];
                }
            }
        }

        return false;
    }
}
