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
 * Add 'edit' button to recurring profiles (admin)
 */

class ParadoxLabs_AuthorizeNetCim_Block_Adminhtml_Sales_Recurring_Profile_View extends Mage_Sales_Block_Adminhtml_Recurring_Profile_View
{
    protected function _prepareLayout()
    {
        $profile 	= Mage::registry('current_recurring_profile');
        $parent		= parent::_prepareLayout();
        
        // Show the edit button if the subscription is active
        if ($profile->canSuspend() && $profile->getMethodCode() == 'authnetcim') {
            $url = $this->getUrl('*/authnetcim_profile/edit', array('profile' => $profile->getId()));
            $this->_addButton('edit', array(
                'label'     => Mage::helper('sales')->__('Modify Recurring Profile'),
                'onclick'   => "setLocation('{$url}')",
                'class'     => '',
            ));
        }

        return $parent;
    }
}
