<?php
/**
 * Authorize.Net CIM - 'Manage My Cards' controller.
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

class ParadoxLabs_AuthorizeNetCim_ManageController extends Mage_Core_Controller_Front_Action
{
	public function preDispatch() {
		parent::preDispatch();

		if( !Mage::getSingleton('customer/session')->authenticate($this) ) {
			$this->getResponse()->setRedirect( Mage::helper('customer')->getLoginUrl() );
			$this->setFlag( '', self::FLAG_NO_DISPATCH, true );
		}

		return $this;
	}
	
	public function indexAction() {
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function deleteAction() {
		$card 	= intval( $this->getRequest()->getParam('c') );
		$key 	= $this->getRequest()->getParam('form_key');
		if( $card > 0 && $key == Mage::getSingleton('core/session')->getFormKey() ) {
			Mage::getModel('authnetcim/payment')->deletePaymentProfile( $card, 0, false );
		}
		
		$this->_redirect( '*/*' );
	}
	
	public function createAction() {
		$card 	= intval( $this->getRequest()->getParam('c') );
		$key 	= $this->getRequest()->getParam('form_key');
		$payment = $this->getRequest()->getParam('payment');
		
		if( is_numeric( $payment['state'] ) ) {
			$payment['state'] = Mage::getModel('directory/region')->load( $payment['state'] )->getName();
		}
		elseif( !empty( $payment['region'] ) ) {
			$payment['state'] = $payment['region'];
		}
		
		if( count($payment) && $key == Mage::getSingleton('core/session')->getFormKey() ) {
			try {
				if( $card > 0 ) {
					Mage::getModel('authnetcim/payment')->updateCustomerPaymentProfile( $card, $payment );
				}
				else {
					Mage::getModel('authnetcim/payment')->createCustomerPaymentProfileFromForm( $payment );
				}
				
				Mage::getSingleton('core/session')->addSuccess( $this->__('Saved changes to the card.') );
			}
			catch( Mage_Core_Exception $e ) {
				Mage::getSingleton('core/session')->addError( $e->getMessage() );
			}
		}
		
		$this->_redirect( '*/*' );
	}
}
