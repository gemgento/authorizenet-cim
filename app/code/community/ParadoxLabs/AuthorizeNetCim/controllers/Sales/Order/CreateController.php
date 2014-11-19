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
 * @category    ParadoxLabs
 * @package     ParadoxLabs_AuthorizeNetCim
 * @author      Ryan Hoerr <ryan@paradoxlabs.com>
 */

/**
 * Default admin order creation does not properly handle
 * nominal items [1.4.0-1.6.?]. This partially fixes that...
 */

require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'Order' . DS . 'CreateController.php';

class ParadoxLabs_AuthorizeNetCim_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController
{
    /**
     * Additional initialization
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * Saving quote and create order
     * 1.4.x gets its own copy.
     */
    public function saveAction()
    {
        $version = Mage::getVersionInfo();
        if( $version['major'] > 1 || $version['minor'] > 4 ) {
            try {
                $this->_processActionData('save');
                if ($paymentData = $this->getRequest()->getPost('payment')) {
                    $this->_getOrderCreateModel()->setPaymentData($paymentData);
                    $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
                }

                $order = $this->_getOrderCreateModel()
                    ->setIsValidate(true)
                    ->importPostData($this->getRequest()->getPost('order'))
                    ->setSendConfirmation(false) // email triggers non-object error
                    ->createOrder();

                $this->_getSession()->clear();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
                
                /**
                 * This is the change:
                 */
                if( $order != null ) {
                    $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
                }
                else {
                    $this->_redirect('*/sales_order/index');
                }
            } catch (Mage_Payment_Model_Info_Exception $e) {
                $this->_getOrderCreateModel()->saveQuote();
                $message = $e->getMessage();
                if( !empty($message) ) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/');
            } catch (Mage_Core_Exception $e){
                $message = $e->getMessage();
                if( !empty($message) ) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/');
            }
            catch (Exception $e){
                $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
                $this->_redirect('*/*/');
            }
        }
        else {
            try {
                $this->_processData();
                if ($paymentData = $this->getRequest()->getPost('payment')) {
                    $this->_getOrderCreateModel()->setPaymentData($paymentData);
                    $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
                }

                $order = $this->_getOrderCreateModel()
                    ->setIsValidate(true)
                    ->importPostData($this->getRequest()->getPost('order'))
                    ->createOrder();

                $this->_getSession()->clear();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
                
                if( $order != null ) {
                    $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
                }
                else {
                    $this->_redirect('*/sales_order/index');
                }
            }
            catch (Mage_Core_Exception $e){
                $message = $e->getMessage();
                if( !empty($message) ) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/');
            }
            catch (Exception $e){
                $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
                $this->_redirect('*/*/');
            }
        }
    }
}
