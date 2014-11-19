<?php
/**
 * Authorize.Net CIM - Recurring profiles edit controller.
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
 * @category	ParadoxLabs
 * @package		ParadoxLabs_AuthorizeNetCim
 * @author		Ryan Hoerr <ryan@paradoxlabs.com>
 */

class ParadoxLabs_AuthorizeNetCim_ProfileController extends Mage_Core_Controller_Front_Action
{
	public function preDispatch() {
		parent::preDispatch();

		if( !Mage::getSingleton('customer/session')->authenticate($this) ) {
			$this->getResponse()->setRedirect( Mage::helper('customer')->getLoginUrl() );
			$this->setFlag( '', self::FLAG_NO_DISPATCH, true );
		}

		return $this;
	}
	
	/**
	 * Change recurring profile addresses et al.
	 */
	public function editAction() {
		$profile	= Mage::getModel('sales/recurring_profile')->load( $this->getRequest()->getParam('profile') );
		$customer	= Mage::getSingleton('customer/session')->getCustomer();
		
		if( $profile && $customer && $profile->getCustomerId() == $customer->getId() ) {
			Mage::register('current_profile', $profile);
			
			if( $profile->getShippingAddressInfo() != array() ) {
				$origAddr	= Mage::getModel('sales/quote_address')->load( $profile->getInfoValue('shipping_address_info', 'address_id') );
				Mage::register('current_address', $origAddr);
			}
			
			$this->loadLayout();
			$this->_title()
				 ->_title( sprintf( 'Subscription %s', $profile->getReferenceId() ) )
				 ->_title('Modify Subscription');
			$this->renderLayout();
		}
		else {
			$this->_redirect('');
		}
	}
	
	/**
	 * Change RP addresses: Form submit.
	 */
	public function editPostAction() {
		Mage::helper('authnetcim')->processRecurringProfileEdits();
		
		$this->_redirect( 'sales/recurring_profile/view', array( 'profile' => $this->getRequest()->getParam('profile') ) );
	}
}
