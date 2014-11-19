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
 * Derived from SARP model: AW_Sarp_Model_Web_Service_Client_Authorizenet
 */

class ParadoxLabs_AuthorizeNetCim_Model_Integration_Sarp_Service extends AW_Sarp_Model_Web_Service_Client_Authorizenet
{
    public function getUri()
    {
        if (Mage::getStoreConfig(ParadoxLabs_AuthorizeNetCim_Model_Integration_Sarp_Payment::XML_PATH_AUTHORIZENET_SOAP_TEST)) {
            return self::SERVICE_TEST_PATH;
        } else {
            return self::SERVICE_PROD_PATH;
        }
    }

    public function getApiLoginId()
    {
        $_storeId = self::DEFAULT_STORE_ID;
        if($this->getSubscription()->getStoreId())
            $_storeId = $this->getSubscription()->getStoreId();
        return Mage::getStoreConfig(ParadoxLabs_AuthorizeNetCim_Model_Integration_Sarp_Payment::XML_PATH_AUTHORIZENET_API_LOGIN_ID, $_storeId);
    }

    public function getTransactionKey()
    {
        $_storeId = self::DEFAULT_STORE_ID;
        if($this->getSubscription()->getStoreId())
            $_storeId = $this->getSubscription()->getStoreId();
        return Mage::getStoreConfig(ParadoxLabs_AuthorizeNetCim_Model_Integration_Sarp_Payment::XML_PATH_AUTHORIZENET_TRANSACTION_KEY, $_storeId);
    }

    public function getPaymentAction()
    {
        $_storeId = self::DEFAULT_STORE_ID;
        if($this->getSubscription() && $this->getSubscription()->getStoreId())
            $_storeId = $this->getSubscription()->getStoreId();
        return Mage::getStoreConfig(ParadoxLabs_AuthorizeNetCim_Model_Integration_Sarp_Payment::XML_PATH_AUTHORIZENET_PAYMENT_ACTION, $_storeId);
    }
}
