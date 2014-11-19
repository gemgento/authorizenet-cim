<?php
/**
 * Authorize.Net CIM
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
 * Default recurring profile workflow deletes the quote
 * after ordering, to avoid processing the order by normal
 * methods. Paypal/IPN must redirect before getting to this
 * point, but our method does not [and cannot]. So we'll
 * repress the exception that's about to be thrown instead...
 */

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php';

class ParadoxLabs_AuthorizeNetCim_OnepageController extends Mage_Checkout_OnepageController
{
    /**
     * Get Order by quoteId
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
    	if (is_null($this->_order)) {
            $this->_order = Mage::getModel('sales/order')->load($this->getOnepage()->getQuote()->getId(), 'quote_id');
    	}
    	
    	return $this->_order;
    }
}
