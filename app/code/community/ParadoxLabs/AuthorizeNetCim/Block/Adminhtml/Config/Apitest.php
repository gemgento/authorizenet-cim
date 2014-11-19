<?php
/**
 * Authorize.Net CIM - Config: API test field
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

class ParadoxLabs_AuthorizeNetCim_Block_Adminhtml_Config_Apitest extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
	protected $renderer	= null;
	protected $store	= null;

	public function render(Varien_Data_Form_Element_Abstract $element) {
		$test = $this->_testApi();
		
		if( $test !== false ) {
			$element->addType( 'authnetcim_apitest', 'ParadoxLabs_AuthorizeNetCim_Block_Adminhtml_Config_Renderer_Apitest' );
			
			$field = $element->addField( 'authnetcim_apitest', 'authnetcim_apitest', array(
				'name'  => 'authnetcim_apitest',
				'label' => 'API Test Results',
				'value' => $test
			))->setRenderer($this->_getRenderer());
			
			return $field->toHtml();
		}
	}
	
	protected function _getRenderer() {
		if( is_null( $this->renderer ) ) {
			$this->renderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
		}
		
		return $this->renderer;
	}
	
	protected function _getConfigData( $field ) {
		if( is_null( $this->store ) ) {
			if( Mage::app()->getRequest()->getParam('store') != '' ) {
				$this->store = Mage::getModel('core/store')->load( Mage::app()->getRequest()->getParam('store') )->getId();
			}
			elseif( Mage::app()->getRequest()->getParam('website') != '' ) {
				$this->store = Mage::getModel('core/website')->load( Mage::app()->getRequest()->getParam('website') )->getDefaultGroup()->getDefaultStoreId();
			}
			else {
				$this->store = 0;
			}
		}
		
		return Mage::getStoreConfig( 'payment/authnetcim/' . $field, $this->store );
	}
	
	protected function _testApi() {
		// Don't bother if details aren't entered.
		if( $this->_getConfigData('login') == '' || $this->_getConfigData('trans_key') == '' ) {
			return false;
		}
		
		$api = Mage::getModel('authnetcim/api')->init(	$this->_getConfigData('login'),
														$this->_getConfigData('trans_key'),
														$this->_getConfigData('test'),
														$this->_getConfigData('validation_mode') );
		
		try {
			// Run the test call -- simple profile request. It won't exist, that's okay.
			$api->setParameter( 'customerProfileId', '0' );
			$api->getCustomerProfile();
			
			// Check for errors
			$code = $api->getCode();
			
			// Bad login ID / trans key
			if( $code == 'E00007' ) {
				return 'Your API credentials are invalid.';
			}
			// CIM not enabled
			elseif( $code == 'E00044' ) {
				return 'Your account does not have CIM enabled.';
			}
			// Okay!
			else {
				return 'Authorize.Net connected successfully.';
			}
		}
		catch( Exception $e ) {}
		
		return 'Unknown error; unable to connect to Authorize.Net.';
	}
}
