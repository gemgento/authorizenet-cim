<?php
/**
 * Authorize.Net CIM - Core bug fix.
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
 * Simple function overload to support admin creation
 * of recurring profile/nominal item orders.
 *
 * For nominal items, $order is null, causing an exception...
 */

class ParadoxLabs_AuthorizeNetCim_Model_Directpost extends Mage_Authorizenet_Model_Directpost_Observer
{
    public function updateAllEditIncrements(Varien_Event_Observer $observer)
    {
        if( $observer->getEvent()->getData('order') != null ) {
            $order = $observer->getEvent()->getData('order');
            Mage::helper('authorizenet')->updateOrderEditIncrements($order);
        }

        return $this;
    }
}
