<?php
/**
 * Authorize.Net CIM - payment info block
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

class ParadoxLabs_AuthorizeNetCim_Block_Info extends Mage_Payment_Block_Info_Cc
{
    /**
     * Prepare credit card related payment info
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        
        $transport = Mage_Payment_Block_Info::_prepareSpecificInformation($transport);
        $data = array();
        
        $ccType = $this->getCcTypeName();
        if ( !empty( $ccType ) && $ccType != 'N/A' ) {
            $data[Mage::helper('payment')->__('Credit Card Type')] = $ccType;
        }
        
        // If this is an eCheck, show different info.
        if ($this->getInfo()->getCcLast4()) {
	        if( $this->getInfo()->getAdditionalInformation('method') == 'ECHECK' ) {
	        	$data[Mage::helper('payment')->__('Paid By')] = Mage::helper('payment')->__('eCheck');
	        	$data[Mage::helper('payment')->__('Account Number')] = sprintf( 'x-%s', $this->getInfo()->getCcLast4() );
	        }
	        else {
            	$data[Mage::helper('payment')->__('Credit Card Number')] = sprintf( 'XXXX-%s', $this->getInfo()->getCcLast4() );
            }
        }
        
        // If this is admin, show different info.
		if( Mage::app()->getStore()->isAdmin() ) {
			$avs = $this->getInfo()->getAdditionalInformation('avs_result_code');
			
			$data[Mage::helper('payment')->__('Transaction ID')] = $this->getInfo()->getAdditionalInformation('transaction_id');
			$data[Mage::helper('payment')->__('AVS Response')] = ( !empty( $avs ) ? $avs : 'N/A' );
        }
        
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
