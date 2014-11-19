<?php
/**
 * Authorize.Net CIM - Admin update feed
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

class ParadoxLabs_AuthorizeNetCim_Model_Feed extends Mage_AdminNotification_Model_Feed
{
	public function getFeedUrl() {
		$protocol = Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://';
		$this->_feedUrl = $protocol . 'store.paradoxlabs.com/updates.php?key=authnetcimrp';
		
		return $this->_feedUrl;
	}
	
	public function observe() {
		$this->checkUpdate();
	}
}
