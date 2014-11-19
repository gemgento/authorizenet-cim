<?php
/**
 * Authorize.Net CIM - Validation mode options
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

class ParadoxLabs_AuthorizeNetCim_Model_Config_Validationmode
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'liveMode', 'label'=>'Live ($0.01 test transaction)'),
            array('value' => 'testMode', 'label'=>'Test (Card number validation only)'),
            array('value' => 'none',     'label'=>'None (Credit cards are not validated)')
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'liveMode'  => 'Live ($0.01 test transaction)',
            'testMode'  => 'Test (Card number validation only)',
            'none'      => 'None (Credit cards are not validated)'
        );
    }
}
