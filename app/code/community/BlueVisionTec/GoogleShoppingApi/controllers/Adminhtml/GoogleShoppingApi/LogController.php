<?php
/**
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * GoogleShopping Admin Items Controller
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author      Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
*/
class BlueVisionTec_GoogleShoppingApi_Adminhtml_GoogleShoppingApi_LogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Initialize general settings for action
     *
     * @return  lueVisionTec_GoogleShoppingApi_Adminhtml_Googleshopping_ItemsController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog/googleshoppingapi/log')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Catalog'), Mage::helper('adminhtml')->__('Catalog'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Google Content'), Mage::helper('adminhtml')->__('Google Content'));
        return $this;
    }

    /**
     * Manage Items page with two item grids: Magento products and Google Content items
     */
    public function indexAction()
    {

        $this->_title($this->__('Catalog'))
             ->_title($this->__('Google Content'))
             ->_title($this->__('Log'));
        
        $this->_initAction()
            ->_addBreadcrumb(Mage::helper('googleshoppingapi')->__('Log'), Mage::helper('googleshoppingapi')->__('Log'))
            ->_addContent($this->getLayout()->createBlock('googleshoppingapi/adminhtml_log'))
            ->renderLayout();
    }

    /**
     * Grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('googleshoppingapi/adminhtml_log_grid')->toHtml()
        );
    }
    
}
