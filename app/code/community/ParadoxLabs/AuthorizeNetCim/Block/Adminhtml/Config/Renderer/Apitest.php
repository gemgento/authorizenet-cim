<?php
/**
 * Authorize.Net CIM - Config: API test field (success/failure renderer)
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

class ParadoxLabs_AuthorizeNetCim_Block_Adminhtml_Config_Renderer_Apitest extends Varien_Data_Form_Element_Label
{
    public function getElementHtml()
    {
        $html = parent::getElementHtml();
        
        if( strpos( $html, 'success' ) !== false ) {
        	$html = '<strong style="color:#0a0;">' . $html . '</strong>';
        }
        else {
        	$html = '<strong class="error">' . $html . '</strong>';
        }
        
        return $html;
    }
}
