<?php
/**
 * Authorize.Net CIM - Config field: Version
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

class ParadoxLabs_AuthorizeNetCim_Model_Config_Version extends Mage_Core_Model_Config_Data
{	
	protected function _afterLoad() {
		$this->setValue( (string)Mage::getConfig()->getNode()->modules->ParadoxLabs_AuthorizeNetCim->version );
	}
}
