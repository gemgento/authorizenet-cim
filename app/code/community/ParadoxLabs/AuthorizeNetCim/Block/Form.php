<?php
/**
 * Authorize.Net CIM - checkout form block.
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

class ParadoxLabs_AuthorizeNetCim_Block_Form extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('authorizenetcim/form.phtml');
    }
    
    public function getPriorCards()
    {
    	if( Mage::getSingleton('authnetcim/payment')->isAvailable() ) {
    		return Mage::getSingleton('authnetcim/payment')->getPaymentInfo();
    	}
    	
    	return false;
    }
}
