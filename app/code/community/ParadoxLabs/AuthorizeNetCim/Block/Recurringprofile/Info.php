<?php
/**
 * Authorize.Net CIM - Recurring profile view / update payment info.
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

class ParadoxLabs_AuthorizeNetCim_Block_Recurringprofile_Info extends Mage_Core_Block_Template
{
	public function _construct() {
		parent::_construct();
		
		$this->setPayment( Mage::getModel('authnetcim/payment') );
		$this->setProfile( Mage::registry('current_recurring_profile') );
		$this->setCustomer( Mage::getModel('customer/customer')->load( $this->getProfile()->getCustomerId() ) );
		
		$this->getPayment()->setStore( $this->getProfile()->getStoreId() );
		
		$post = Mage::app()->getRequest()->getPost();
		if( !empty( $post ) && $post['form_key'] == Mage::getSingleton('core/session')->getFormKey() ) {
			if( isset($post['set_cc']) && intval($post['payment_id']) > 0 ) {
				$info = $this->getProfile()->getAdditionalInfo();
				$info['payment_id'] = intval($post['payment_id']);
				$this->getProfile()->setAdditionalInfo( $info )->save();
				
				Mage::log( 'Changed payment ID for RP #'.$this->getProfile()->getReferenceId().' to '.$info['payment_id'], null, 'authnetcim.log' );
			}
			elseif( isset( $post['set_next_billed'] ) && strtotime( $post['next_billed'] ) > 0 ) {
				$info = $this->getProfile()->getAdditionalInfo();
				$info['next_cycle'] = Mage::getModel('core/date')->gmtTimestamp( $post['next_billed'] );
				$this->getProfile()->setAdditionalInfo( $info )->save();
				
				Mage::log( 'Changed next billing cycle for RP #'.$this->getProfile()->getReferenceId().' to '.date( 'j-F Y H:i', Mage::getModel('core/date')->timestamp( $info['next_cycle'] ) ), null, 'authnetcim.log' );
			}
		}
	}
	
	public function isAuthnetcim() {
		return ($this->getProfile()->getMethodCode() == 'authnetcim');
	}
	
	public function getLastBilled() {
		$date = $this->getProfile()->getAdditionalInfo('last_bill');
		return $date > 0 ? date( 'j-F Y H:i', Mage::getModel('core/date')->timestamp( $date ) ) : 'Never';
	}
	
	public function getNextBilled() {
		$date = $this->getNextBilledRaw();
		
		if( $date > 0 ) {
			return date( 'j-F Y H:i', Mage::getModel('core/date')->timestamp( $date ) );
		}
		
		return 'N/A';
	}
	
	public function getNextBilledRaw() {
		$okayStates	= array( 'active', 'pending' );
		$date		= $this->getProfile()->getAdditionalInfo('next_cycle');
		
		if( in_array( $this->getProfile()->getState(), $okayStates ) && $date > 0 ) {
			return $date;
		}
		
		return false;
	}
	
	public function getPaymentInfo() {
		return $this->getPayment()->getPaymentInfoById( $this->getProfile()->getAdditionalInfo('payment_id'), false, $this->getCustomer()->getAuthnetcimProfileId() );
	}
	
	public function getAllCards() {
		return $this->getPayment()->getPaymentInfo( $this->getCustomer()->getAuthnetcimProfileId() );
	}
}
