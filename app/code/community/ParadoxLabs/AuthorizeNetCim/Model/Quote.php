<?php
/**
 * Authorize.Net CIM - Core bug fix.
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
 * Simple function overload to support admin creation
 * of recurring profile/nominal item orders.
 *
 * submit() is deprecated, but they did it wrong.
 *
 * arg.
 */

class ParadoxLabs_AuthorizeNetCim_Model_Quote extends Mage_Sales_Model_Service_Quote
{
	/**
	 * @deprecated after 1.4.0.1
	 * @see submitOrder()
	 * @see submitAll()
	 */
	public function submit()
	{
		// return $this->submitOrder();
		$this->submitAll();
		
		/**
		 * Not sure how this is supposed to work with multiple recurring profiles...
		 * but there can only be one profile in an order right now regardless.
		 */
		$profiles = $this->getRecurringPaymentProfiles();
		if( $this->_quote->getIsActive() === false && count($profiles) > 0 ) {
			// Trigger RP fetch
			$this->getOrder();
			
			Mage::dispatchEvent('sales_model_service_quote_submit_before', array('order'=>$this->_order, 'quote'=>$this->_quote));
			
			$transaction = Mage::getModel('core/resource_transaction');
			if ($this->_quote->getCustomerId()) {
				$transaction->addObject($this->_quote->getCustomer());
			}
			$transaction->addObject($this->_quote);
			$transaction->addObject($this->_order);
			$transaction->addCommitCallback(array($this->_order, 'place'));
			$transaction->addCommitCallback(array($this->_order, 'save'));
			$transaction->save();
			
			Mage::dispatchEvent('sales_model_service_quote_submit_success', array('order'=>$this->_order, 'quote'=>$this->_quote));
			Mage::dispatchEvent('sales_model_service_quote_submit_after', array('order'=>$this->_order, 'quote'=>$this->_quote));
		}

		return $this->_order;
	}
	
	/**
	 * Handle order properly for recurring profiles... why does Magento not do this?
	 */
    public function getOrder()
    {
    	if( isset($this->_order) ) {
    		return $this->_order;
    	}
    	
		$profiles = $this->getRecurringPaymentProfiles();
		if( count($profiles) > 0 ) {
			foreach( $profiles as $profile ) {
				$orders = $profile->getChildOrderIds();
				if( count( $orders ) > 0 ) {
					$order = array_pop( $orders );
					
					if( $order > 0 ) {
						$this->_order = Mage::getModel('sales/order')->load( $order );
					}
				}
			}
		}
		
        return $this->_order;
    }
}
