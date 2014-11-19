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
 * @category	ParadoxLabs
 * @package		ParadoxLabs_AuthorizeNetCim
 * @author		Ryan Hoerr <ryan@paradoxlabs.com>
 */

class ParadoxLabs_AuthorizeNetCim_Block_Recurringprofile_Edit extends Mage_Checkout_Block_Onepage_Abstract
{
    protected $_address = null;
    
	public function _construct() {
		parent::_construct();
		
		$this->setProfile( Mage::registry('current_profile') );
		
		$customer	= Mage::getModel('customer/customer')->load( $this->getProfile()->getCustomerId() );
		$payment	= Mage::getModel('authnetcim/payment');
		
		$payment->setCustomer( $customer )
				->setStore( $this->getProfile()->getStoreId() );
		
		$this->setCustomer( $customer );
		$this->setPayment( $payment );
	}
	
    public function getCustomer()
    {
        return $this->getData('customer');
    }

    public function isCustomerLoggedIn()
    {
        return $this->getData('customer')->getId() > 0 ? true : false;
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
    
    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = Mage::registry('current_address');
        }

        return $this->_address;
    }
    
    public function isShow()
    {
        return true;
    }
}
