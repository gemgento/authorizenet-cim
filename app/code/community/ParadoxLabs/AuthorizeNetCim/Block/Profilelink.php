<?php
/**
 * Authorize.Net CIM - 'Manage My Cards' conditional my account link.
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

class ParadoxLabs_AuthorizeNetCim_Block_Profilelink extends Mage_Core_Block_Template
{
	public function addProfileLink() {
	    if( ($parentBlock = $this->getParentBlock()) && Mage::getModel('authnetcim/payment')->isAvailable() ) {
	        $parentBlock->addLink( 'authnetcim', 'authnetcim/manage/', $this->__("Manage My Cards"), array( '_secure' => true ) );
	    }
	}
}
