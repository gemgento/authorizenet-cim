<?php

class ParadoxLabs_AuthorizeNetCim_Model_Payment_Api extends Mage_Catalog_Model_Api_Resource {

    /**
     * Get a list of saved credit cards from authorize net cim extension.
     *
     * @param int $customerId
     * @return array
     */
    public function items($customerId) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $cards = Mage::getModel('authnetcim/payment')->setCustomer( $customer )->getPaymentProfiles();
        $paymentProfiles = array();

        /**
         * Get customer's active orders and check for card conflicts.
         */
        $orders	= Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect( '*' )
            ->addAttributeToFilter( 'customer_id', $customerId )
            ->addAttributeToFilter( 'status', array( 'like' => 'pending%' ) );

        if( $cards !== false && count($cards) > 0 ) {
            foreach( $cards as $card ) {
                $card->inUse = 0;

                if( count($orders) > 0 ) {
                    foreach( $orders as $order ) {
                        if( $order->getExtCustomerId() == $card->customerPaymentProfileId && $order->getPayment()->getMethod() == 'authnetcim' ) {
                            // If we found an order with this card that is not complete, closed, or canceled,
                            // it is still active and the payment ID is important. No editey.
                            $card->inUse = 1;
                            break;
                        }
                    }
                }

                $paymentProfiles[] = $card;
            }
        }

        return $paymentProfiles;
    }

    /**
     * Create a saved credit card
     *
     * @param array(string) $payment
     * @return boolean
     */
    public function create($customerId, $payment) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $payment = get_object_vars($payment);

        if( is_numeric( $payment['state'] ) ) {
            $payment['state'] = Mage::getModel('directory/region')->load( $payment['state'] )->getName();
        }
        elseif( !empty( $payment['region'] ) ) {
            $payment['state'] = $payment['region'];
        }

        try {
            Mage::getModel('authnetcim/payment')->setCustomer( $customer )->createCustomerPaymentProfileFromForm( $payment );
            return true;
        }
        catch( Mage_Core_Exception $e ) {
            Mage::getSingleton('core/session')->addError( $e->getMessage() );
        }
    }

    /**
     * Destroy a saved credit card.
     *
     * @param int $customerId
     * @param int $paymentProfileId
     * @return bool
     */
    public function destroy($customerId, $paymentProfileId) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $payment = Mage::getModel('authnetcim/payment')->setCustomer($customer);

        if ($payment->deletePaymentProfile( $paymentProfileId, 0, false )) {
            return true;
        } else {
            return false;
        }
    }

}