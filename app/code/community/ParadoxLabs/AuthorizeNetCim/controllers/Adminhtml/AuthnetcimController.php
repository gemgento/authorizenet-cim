<?php
/**
 * Authorize.Net CIM - Customer card manager controller
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

class ParadoxLabs_AuthorizeNetCim_Adminhtml_AuthnetcimController extends Mage_Adminhtml_Controller_Action
{
	public function editAction() {
		echo Mage::app()->getLayout()->createBlock('authnetcim/adminhtml_customer_edit')->toHtml();
	}
	
	public function saveAction() {
		$card		= intval( $this->getRequest()->getParam('c') );
		$key		= $this->getRequest()->getParam('form_key');
		$payment	= $this->getRequest()->getParam('payment');
		$customer	= Mage::getModel('customer/customer')->load( $this->getRequest()->getParam('id') );
		
		if( is_numeric( $payment['state'] ) ) {
			$payment['state'] = Mage::getModel('directory/region')->load( $payment['state'] )->getName();
		}
		elseif( !empty( $payment['region'] ) ) {
			$payment['state'] = $payment['region'];
		}
		
		if( count($payment) && $key == Mage::getSingleton('core/session')->getFormKey() ) {
			try {
				if( $card > 0 ) {
					Mage::getModel('authnetcim/payment')->setCustomer( $customer )->updateCustomerPaymentProfile( $card, $payment, $customer->getAuthnetcimProfileId() );
				}
				else {
					Mage::getModel('authnetcim/payment')->setCustomer( $customer )->createCustomerPaymentProfileFromForm( $payment, $customer->getAuthnetcimProfileId() );
				}
			}
			catch( Mage_Core_Exception $e ) {
				Mage::log( 'Failed admin card creation/edit.', null, 'authnetcim.log' );
				Mage::log( $e, null, 'authnetcim.log' );
			}
		}
		
		echo Mage::app()->getLayout()->createBlock('authnetcim/adminhtml_customer_view')->toHtml();
	}

	public function deleteAction() {
		$card		= intval( $this->getRequest()->getParam('c') );
		$key		= $this->getRequest()->getParam('form_key');
		$customer	= Mage::getModel('customer/customer')->load( $this->getRequest()->getParam('id') );
		
		if( $card > 0 && $key == Mage::getSingleton('core/session')->getFormKey() ) {
			Mage::getModel('authnetcim/payment')->setCustomer( $customer )->deletePaymentProfile( $card, $customer->getAuthnetcimProfileId(), false );
		}
		
		echo 1;
	}
}
