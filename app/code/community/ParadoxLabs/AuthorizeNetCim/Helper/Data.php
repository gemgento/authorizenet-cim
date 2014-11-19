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

/**
 * Helper methods
 */

class ParadoxLabs_AuthorizeNetCim_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Take form inputs and updates a recurring profile and customer data accordingly.
	 * Expects very particular inputs.
	 */
	public function processRecurringProfileEdits() {
		$profile	= Mage::getModel('sales/recurring_profile')->load( Mage::app()->getRequest()->getParam('profile') );
		$customer	= Mage::getmodel('customer/customer')->load( $profile->getCustomerId() );
		
		try {
			if( $profile && $customer && $profile->getCustomerId() == $customer->getId() ) {
				
				if( $profile->getShippingAddressInfo() != array() ) {
					$origAddr	= Mage::getModel('sales/quote_address')->load( $profile->getInfoValue('shipping_address_info', 'address_id') );
					$newAddrId	= intval( Mage::app()->getRequest()->getParam('shipping_address_id') );
					
					// Has the address changed?
					if( $origAddr && $newAddrId != $origAddr->getCustomerAddressId() ) {
						/**
						 * New address or existing?
						 * 
						 * If new:
						 * - store as customer address
						 * - convert to quote address
						 * - add to profile
						 * 
						 * If existing:
						 * - convert to quote address
						 * - add to profile
						 */
						
						// Existing address
						if( $newAddrId > 0 ) {
							$newAddr = Mage::getModel('customer/address')->load( $newAddrId );
							
							if( $newAddr->getCustomerId() != $customer->getId() ) {
								Mage::throwException( $this->__('An error occurred. Please try again.') );
							}
						}
						// New address
						else {
							$newAddr = Mage::getModel('customer/address');
							$newAddr->setCustomerId( $customer->getId() );
							
							$data = Mage::app()->getRequest()->getPost('shipping', array());
							
							$addressForm = Mage::getModel('customer/form');
							$addressForm->setFormCode('customer_address_edit');
							$addressForm->setEntity( $newAddr );
							
							$addressData    = $addressForm->extractData( $addressForm->prepareRequest( $data ) );
							$addressErrors  = $addressForm->validateData( $addressData );
							
							if( $addressErrors !== true ) {
								Mage::throwException( $addressErrors );
							}
							
							$addressForm->compactData( $addressData );
							$addressErrors = $newAddr->validate();
							
							$newAddr->setSaveInAddressBook( true );
							$newAddr->implodeStreetAddress();
							$newAddr->save();
						}
						
						// Update the shipping address on our record
						$origAddr->importCustomerAddress( $newAddr );
						
						$shippingAddr = $origAddr->getData();
						$this->cleanupArray( $shippingAddr );
						$profile->setShippingAddressInfo( $shippingAddr );
					}
				}
				
				$payment_id = intval( Mage::app()->getRequest()->getParam('payment_id') );
				if( $payment_id > 0 && $payment_id != $profile->getInfoValue('additional_info', 'payment_id') ) {
					// Change billing ID
					$adtl = $profile->getAdditionalInfo();
					$adtl['payment_id'] = $payment_id;
					
					// Update billing address to match the card
					$payment = Mage::getModel('authnetcim/payment');
					$payment->setStore( $profile->getStoreId() )
							->setCustomer( $customer );
					
					$card    = $payment->getPaymentInfoById( $payment_id, true, $profile->getInfoValue('additional_info', 'profile_id') );
					
					if( $card && $card->billTo ) {
						$billingAddr = $profile->getBillingAddressInfo();
						$billingAddr['street']		= (string)$card->billTo->address;
						$billingAddr['firstname']	= (string)$card->billTo->firstName;
						$billingAddr['lastname']	= (string)$card->billTo->lastName;
						$billingAddr['city']		= (string)$card->billTo->city;
						$billingAddr['region']		= (string)$card->billTo->state;
						$billingAddr['region_id']	= Mage::getModel('directory/region')->load( (string)$card->billTo->state, 'default_name' )->getId();
						$billingAddr['postcode']	= (string)$card->billTo->zip;
						$billingAddr['country_id']	= (string)$card->billTo->country;
						
						$profile->setBillingAddressInfo( $billingAddr );
						$profile->setAdditionalInfo( $adtl );
						
						Mage::log( 'Changed payment ID for RP #'.$profile->getReferenceId().' to '.$adtl['payment_id'], null, 'authnetcim.log' );
					}
					else {
						Mage::throwException( $this->__('Credit card record not found. Please try again.') );
					}
				}
				
				// Change next billing date
				$next_billed = Mage::getModel('core/date')->gmtTimestamp( Mage::app()->getRequest()->getParam('next_billed') );
				if( Mage::app()->getRequest()->getParam('next_billed') != '' && $next_billed > 0 && $next_billed != $profile->getInfoValue('additional_info', 'next_cycle') ) {
					$info = $profile->getAdditionalInfo();
					$info['next_cycle'] = $next_billed;
					$profile->setAdditionalInfo( $info );
					
					Mage::log( 'Changed next billing cycle for RP #'.$profile->getReferenceId().' to '.date( 'j-F Y h:i', Mage::getModel('core/date')->timestamp( $info['next_cycle'] ) ), null, 'authnetcim.log' );
				}
				
				Mage::dispatchEvent( 'authnetcim_recurringprofile_edit_before_save', array( 'profile' => $profile ) );
				
				$profile->save();
				
				Mage::getSingleton('core/session')->addSuccess( $this->__('Updated your recurring profile settings.') );
			}
			else {
				Mage::throwException( $this->__('An error occurred. Please try again.') );
			}
		}
		catch( Exception $e ) {
			Mage::getSingleton('core/session')->addError( $e->getMessage() );
		}
		
		return $this;
	}
	
	/**
	 * Recursively remove objects from an array
	 */
	public function cleanupArray(&$array) {
		if (!$array) {
			return;
		}
		foreach ($array as $key => $value) {
			if (is_object($value)) {
				unset($array[$key]);
			} elseif (is_array($value)) {
				$this->cleanupArray($array[$key]);
			}
		}
	}
}
