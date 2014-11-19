<?php
/**
 * Authorize.Net CIM - RP management controller
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

class ParadoxLabs_AuthorizeNetCim_Adminhtml_Authnetcim_ProfileController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * 'edit' recurring profile page
	 */
	public function editAction() {
		$profile	= Mage::getModel('sales/recurring_profile')->load( $this->getRequest()->getParam('profile') );
		$customer	= Mage::getmodel('customer/customer')->load( $profile->getCustomerId() );
		
		if( $profile && $customer && $profile->getCustomerId() == $customer->getId() ) {
			Mage::register('current_profile', $profile);
			
			if( $profile->getShippingAddressInfo() != array() ) {
				$origAddr	= Mage::getModel('sales/quote_address')->load( $profile->getInfoValue('shipping_address_info', 'address_id') );
				Mage::register('current_address', $origAddr);
			}
			
			$this->loadLayout();
			$this->_title( sprintf( 'Subscription %s', $profile->getReferenceId() ) )
				 ->_title('Modify Subscription');
			$this->renderLayout();
		}
		else {
			$this->_redirect('*/sales_recurring_profile/view', array('profile' => $this->getRequest()->getParam('profile')));
		}
	}
	
	/**
	 * Change RP shipping address: Form submit.
	 */
	public function editPostAction() {
		Mage::helper('authnetcim')->processRecurringProfileEdits();
		
		$this->_redirect('*/sales_recurring_profile/view', array('profile' => $this->getRequest()->getParam('profile')));
	}
}
