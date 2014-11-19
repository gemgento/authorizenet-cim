<?php
/**
 * Authorize.Net CIM - AheadWorks SARP payment integration
 *
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Having a problem with the plugin?
 * Not sure what something means?
 * Need custom development?
 * Give us a call!
 *
 * @category    ParadoxLabs
 * @package     ParadoxLabs_AuthorizeNetCim
 * @author      Ryan Hoerr <ryan@paradoxlabs.com>
 */

/**
 * Derived from SARP model: AW_Sarp_Model_Payment_Method_Authorizenet
 */

class ParadoxLabs_AuthorizeNetCim_Model_Integration_Sarp_Payment extends AW_Sarp_Model_Payment_Method_Abstract
{

    const PAYMENT_METHOD_CODE = 'authnetcim';

    const XML_PATH_AUTHORIZENET_API_LOGIN_ID = 'payment/authnetcim/login';
    const XML_PATH_AUTHORIZENET_TEST_MODE = 'payment/authnetcim/test';
    const XML_PATH_AUTHORIZENET_DEBUG = 'payment/authnetcim/debug';
    const XML_PATH_AUTHORIZENET_TRANSACTION_KEY = 'payment/authnetcim/trans_key';
    const XML_PATH_AUTHORIZENET_PAYMENT_ACTION = 'payment/authnetcim/payment_action';
    const XML_PATH_AUTHORIZENET_ORDER_STATUS = 'payment/authnetcim/order_status';
    const XML_PATH_AUTHORIZENET_SOAP_TEST = 'payment/authnetcim/test';

    const WEB_SERVICE_MODEL = 'sarp/web_service_client_authnetcim';

    public function __construct()
    {
        $this->_initWebService();
    }

    /**
     * Initializes web service instance
     * @return AW_Sarp_Model_Payment_Method_Authorizenet
     */
    protected function _initWebService()
    {
        $service = Mage::getModel(self::WEB_SERVICE_MODEL);
        $this->setWebService($service);
        return $this;
    }

    /**
     * This function is run when subscription is created and new order creates
     * @param AW_Sarp_Model_Subscription $Subscription
     * @param Mage_Sales_Model_Order     $Order
     * @param Mage_Sales_Model_Quote     $Quote
     * @return AW_Sarp_Model_Payment_Method_Abstract
     */
    public function onSubscriptionCreate(AW_Sarp_Model_Subscription $Subscription, Mage_Sales_Model_Order $Order, Mage_Sales_Model_Quote $Quote)
    {
        $this->createSubscription($Subscription, $Order, $Quote);
        return $this;
    }

    public function onBillingAddressChange(AW_Sarp_Model_Subscription $Subscription, $billingAddress)
    {
        $service = $this->getWebService()
                            ->setSubscription($Subscription)
                            ->setBillingAddress($billingAddress);
        
        $service->updateBillingAddress();
        
        return $this;
    }

    public function createSubscription($Subscription, $Order, $Quote)
    {
		$customerProfileID = $Order->getPayment()->getAdditionalInformation('profile_id');
		$customerPaymentID = $Order->getPayment()->getAdditionalInformation('payment_id');
    	
        $Subscription
                ->setRealId($customerProfileID)
                ->setRealPaymentId($customerPaymentID)
                ->save();
        
        return $this;
    }

    /**
     * Process payment for specified order
     * @param Mage_Sales_Model_Order $Order
     * @return
     */
    public function processOrder(Mage_Sales_Model_Order $PrimaryOrder, Mage_Sales_Model_Order $Order = null)
    {
        if ($Order->getBaseGrandTotal() > 0) {
            $result = $this->getWebService()
                    ->setSubscription($this->getSubscription())
                    ->setOrder($Order)
                    ->createTransaction();
            $ccTransId = @$result->transactionId;
            
            // Try to find an auth code
            $auth = '';
            if( strlen( $result->directResponse ) > 1 ) {
            	$response = explode( substr( str_replace( '"', '', $result->directResponse ), 1, 1 ), $result->directResponse );
            	
            	if( isset( $response[4] ) && !empty( $response[4] ) ) {
            		$auth = ':' . $response[4];
            	}
            }
            
            // Set data for invoice actions in authnetcim
            $adtl = array(
            	'profile_id'		=> $this->getSubscription()->getRealId(),
            	'payment_id'		=> $this->getSubscription()->getRealPaymentId(),
            	'transaction_id'	=> $ccTransId
            );
            
            $Order->getPayment()->setCcTransId( $ccTransId );
            $Order->getPayment()->setTransactionId( $ccTransId );
            $Order->getPayment()->setAdditionalInfo( $adtl );
            $Order->setExtCustomerId( $this->getSubscription()->getRealPaymentId() );
            $Order->setExtOrderId( $ccTransId . $auth );
            $Order->getPayment()->setSubscriptionId( $this->getSubscription()->getId() );
        }
    }
}
