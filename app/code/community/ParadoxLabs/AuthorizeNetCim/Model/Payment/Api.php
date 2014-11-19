<?php

class ParadoxLabs_AuthorizeNetCim_Model_Payment_Api extends Mage_Catalog_Model_Api_Resource {

    /**
     * Get a list of saved credit cards from authorize net cim extension.
     *
     * @param int $customerId
     * @return array
     */
    public function items($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $paymentProfiles = Mage::getModel('authnetcim/payment')->setCustomer( $customer )->getPaymentProfiles();

        if (empty($paymentProfiles)){
            return array();
        } else if (!is_array($paymentProfiles)) {
            $paymentProfiles = array($paymentProfiles);
        }

        return $paymentProfiles;
    }

}